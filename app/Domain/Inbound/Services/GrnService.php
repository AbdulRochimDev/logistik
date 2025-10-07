<?php

namespace App\Domain\Inbound\Services;

use App\Domain\Inbound\DTO\PostGrnData;
use App\Domain\Inbound\DTO\PostGrnLineData;
use App\Domain\Inbound\Models\GrnHeader;
use App\Domain\Inbound\Models\GrnLine;
use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

class GrnService
{
    public function __construct(private readonly StockService $stockService) {}

    public function post(PostGrnData $data): array
    {
        return DB::transaction(function () use ($data) {
            $inbound = InboundShipment::query()
                ->with(['purchaseOrder.supplier'])
                ->lockForUpdate()
                ->findOrFail($data->inboundShipmentId);

            $externalKey = $data->externalIdempotencyKey;

            if ($data->grnHeaderId) {
                $grnHeader = GrnHeader::query()->lockForUpdate()->findOrFail($data->grnHeaderId);

                if ($grnHeader->inbound_shipment_id !== $inbound->id) {
                    throw new StockException('GRN header does not belong to inbound shipment.', 409);
                }

                if ($grnHeader->external_idempotency_key) {
                    if ($externalKey && $externalKey !== $grnHeader->external_idempotency_key) {
                        throw new StockException('Supplied idempotency key conflicts with existing GRN header.', 409);
                    }

                    $externalKey = $grnHeader->external_idempotency_key;
                } else {
                    $externalKey = $externalKey ?? $this->generateDeterministicKey($data, $inbound);
                    $grnHeader->external_idempotency_key = $externalKey;
                    $grnHeader->save();
                }

                $wasCreated = false;
            } else {
                $externalKey = $externalKey ?? $this->generateDeterministicKey($data, $inbound);

                $grnHeader = GrnHeader::query()
                    ->lockForUpdate()
                    ->where('external_idempotency_key', $externalKey)
                    ->first();

                if ($grnHeader) {
                    if ($grnHeader->inbound_shipment_id !== $inbound->id) {
                        throw new StockException('Idempotency key already used for another inbound shipment.', 409);
                    }

                    $wasCreated = false;
                } else {
                    $grnHeader = GrnHeader::query()->create([
                        'inbound_shipment_id' => $inbound->id,
                        'grn_no' => $this->generateGrnNumber(),
                        'received_at' => $data->receivedAt,
                        'status' => 'draft',
                        'received_by' => $data->receivedBy,
                        'verified_by' => null,
                        'notes' => $data->notes,
                        'external_idempotency_key' => $externalKey,
                    ]);
                    $wasCreated = true;
                }
            }

            $processed = 0;
            $skipped = 0;
            $movements = [];

            /** @var PostGrnLineData $line */
            foreach ($data->lines as $line) {
                /** @var PoItem $poItem */
                $poItem = PoItem::query()
                    ->with('item', 'purchaseOrder')
                    ->lockForUpdate()
                    ->findOrFail($line->poItemId);

                if ($poItem->purchase_order_id !== $inbound->purchase_order_id) {
                    throw new StockException('PO item does not belong to the inbound shipment.');
                }

                if ($poItem->item_id !== $line->itemId) {
                    throw new StockException('PO item mismatch with provided item.');
                }

                /** @var Location $location */
                $location = Location::query()->findOrFail($line->toLocationId);

                if ($location->warehouse_id !== $poItem->purchaseOrder->warehouse_id) {
                    throw new StockException('Target location warehouse mismatch.');
                }

                /** @var Item $item */
                $item = $poItem->item;
                $itemLot = null;

                if ($item->is_lot_tracked) {
                    if (! $line->lotNo) {
                        throw new StockException('Lot number required for lot tracked item.');
                    }

                    /** @var ItemLot $itemLot */
                    $itemLot = ItemLot::query()->firstOrCreate(
                        ['item_id' => $item->id, 'lot_no' => $line->lotNo],
                        []
                    );
                }

                $existingLine = GrnLine::query()
                    ->where('grn_header_id', $grnHeader->id)
                    ->where('po_item_id', $poItem->id)
                    ->when($itemLot, fn ($query) => $query->where('item_lot_id', $itemLot->id))
                    ->when(! $itemLot, fn ($query) => $query->whereNull('item_lot_id'))
                    ->where('putaway_location_id', $line->toLocationId)
                    ->lockForUpdate()
                    ->first();

                $receivedExcludingCurrent = (float) $poItem->grnLines()->sum('received_qty');

                if ($existingLine) {
                    $receivedExcludingCurrent -= (float) $existingLine->received_qty;
                }

                if ($receivedExcludingCurrent + $line->quantity - (float) $poItem->ordered_qty > 0.0001) {
                    throw new StockException('Received quantity exceeds outstanding purchase order quantity.');
                }

                $grnLine = GrnLine::query()->updateOrCreate(
                    [
                        'grn_header_id' => $grnHeader->id,
                        'po_item_id' => $poItem->id,
                        'item_lot_id' => $itemLot?->id,
                        'putaway_location_id' => $location->id,
                    ],
                    [
                        'received_qty' => $line->quantity,
                        'rejected_qty' => 0,
                        'uom' => $poItem->uom,
                    ]
                );

                $movement = $this->stockService->move(
                    type: 'inbound_putaway',
                    warehouseId: $location->warehouse_id,
                    itemId: $item->id,
                    lotId: $itemLot?->id,
                    fromLocationId: null,
                    toLocationId: $location->id,
                    qty: $line->quantity,
                    uom: $poItem->uom,
                    refType: 'GRN',
                    refId: (string) $grnLine->id,
                    actorUserId: $data->receivedBy,
                    movedAt: $data->receivedAt,
                    remarks: $data->notes,
                );

                $movement->wasRecentlyCreated ? $processed++ : $skipped++;

                $movements[] = $movement;

                $poItem->received_qty = $poItem->grnLines()->sum('received_qty');
                $poItem->save();
            }

            $grnHeader->fill([
                'received_at' => $data->receivedAt,
                'received_by' => $data->receivedBy,
                'status' => 'posted',
                'notes' => $data->notes,
            ])->save();

            return [
                'header' => $grnHeader->fresh('lines'),
                'movements' => $movements,
                'metadata' => [
                    'external_idempotency_key' => $externalKey,
                    'lines_processed' => $processed,
                    'lines_skipped' => $skipped,
                    'created' => $wasCreated,
                ],
            ];
        });
    }

    private function generateDeterministicKey(PostGrnData $data, InboundShipment $inbound): string
    {
        $supplierId = $inbound->purchaseOrder?->supplier_id;

        $canonicalLines = collect($data->lines)
            ->map(fn (PostGrnLineData $line) => [
                'item_id' => $line->itemId,
                'lot_no' => $line->lotNo ?? '',
                'to_location_id' => $line->toLocationId,
                'qty' => $line->quantity,
            ])
            ->sortBy(fn ($line) => json_encode($line))
            ->values()
            ->all();

        try {
            $hash = hash('sha256', json_encode($canonicalLines, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new StockException('Unable to calculate GRN idempotency signature.');
        }

        return sprintf('GRN|%s|%s|%s', $supplierId ?? '', $inbound->id, $hash);
    }

    private function generateGrnNumber(): string
    {
        return sprintf('GRN-%s-%s', now()->format('YmdHis'), Str::upper(Str::random(4)));
    }
}
