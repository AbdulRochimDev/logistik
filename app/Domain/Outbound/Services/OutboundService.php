<?php

namespace App\Domain\Outbound\Services;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Models\Pod;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OutboundService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @return array{movement: StockMovement, so_item: SoItem}
     */
    public function allocate(
        SoItem $soItem,
        float $qty,
        Location $location,
        ?ItemLot $itemLot,
        string $idempotencyKey,
        ?int $actorUserId,
        CarbonImmutable $allocatedAt,
        ?string $remarks = null
    ): array {
        if ($qty <= 0) {
            throw new StockException('Allocation quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($soItem, $qty, $location, $itemLot, $idempotencyKey, $actorUserId, $allocatedAt, $remarks) {
            $soItem->loadMissing('salesOrder.warehouse');
            $salesOrder = $soItem->salesOrder;

            if (! $salesOrder) {
                throw new StockException('Sales order not found for item.');
            }

            if ($location->warehouse_id !== $salesOrder->warehouse_id) {
                throw new StockException('Allocation location must be in the same warehouse as the sales order.');
            }

            $outstanding = $soItem->ordered_qty - $soItem->allocated_qty;
            if ($outstanding + 0.0001 < $qty) {
                throw new StockException('Allocation exceeds outstanding order quantity.');
            }

            /** @var Stock|null $stock */
            $stock = $location->stocks()
                ->where('item_id', $soItem->item_id)
                ->when($itemLot, static function ($query) use ($itemLot) {
                    return $query->where('item_lot_id', $itemLot->id);
                })
                ->when(! $itemLot, static function ($query) {
                    return $query->whereNull('item_lot_id');
                })
                ->lockForUpdate()
                ->first();

            $available = $stock ? (float) $stock->qty_available : 0.0;
            if ($available + 0.0001 < $qty) {
                throw new StockException('Insufficient available quantity to allocate.');
            }

            $movement = $this->stockService->move(
                type: 'allocate',
                warehouseId: $salesOrder->warehouse_id,
                itemId: $soItem->item_id,
                lotId: $itemLot?->id,
                fromLocationId: $location->id,
                toLocationId: null,
                qty: $qty,
                uom: $soItem->uom,
                refType: 'SO_ALLOC',
                refId: sprintf('%s|%s', $soItem->id, $idempotencyKey),
                actorUserId: $actorUserId,
                movedAt: $allocatedAt,
                remarks: $remarks,
            );

            if ($movement->wasRecentlyCreated) {
                $soItem->allocated_qty += $qty;
                $soItem->save();
            }

            return [
                'movement' => $movement,
                'so_item' => $soItem->fresh(),
            ];
        });
    }

    /**
     * @return array{movement: StockMovement, shipment_item: ShipmentItem, shipment: Shipment}
     */
    public function completePick(PickLineData $data): array
    {
        if ($data->quantity <= 0) {
            throw new StockException('Pick quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($data) {
            /** @var ShipmentItem $shipmentItem */
            $shipmentItem = ShipmentItem::query()
                ->with(['shipment.outboundShipment.salesOrder', 'salesOrderItem'])
                ->lockForUpdate()
                ->findOrFail($data->shipmentItemId);

            $shipment = $shipmentItem->shipment;

            if ($shipment->status === 'delivered') {
                throw new StockException('Shipment already delivered.');
            }

            $shipment->loadMissing('outboundShipment.salesOrder');
            $outboundShipment = $shipment->outboundShipment;
            $salesOrder = $outboundShipment?->salesOrder;
            if (! $outboundShipment || ! $salesOrder) {
                throw new StockException('Shipment is not linked to a sales order.');
            }

            if ($shipmentItem->from_location_id === null) {
                throw new StockException('Shipment item missing pick location.');
            }

            $remaining = $shipmentItem->qty_planned - $shipmentItem->qty_picked;
            if ($data->quantity > $remaining + 0.0001) {
                throw new StockException('Pick quantity exceeds planned amount.');
            }

            $salesOrderItem = $shipmentItem->salesOrderItem;
            $uom = $salesOrderItem ? $salesOrderItem->uom : 'PCS';

            $movement = $this->stockService->move(
                type: 'pick',
                warehouseId: $salesOrder->warehouse_id,
                itemId: $shipmentItem->item_id,
                lotId: $shipmentItem->item_lot_id,
                fromLocationId: $shipmentItem->from_location_id,
                toLocationId: null,
                qty: $data->quantity,
                uom: $uom,
                refType: 'PICK',
                refId: sprintf('%d|%s', $shipmentItem->id, $data->idempotencyKey),
                actorUserId: $data->actorUserId,
                movedAt: $data->pickedAt,
                remarks: $data->remarks,
            );

            if ($movement->wasRecentlyCreated) {
                $shipmentItem->qty_picked += $data->quantity;
                $shipmentItem->qty_shipped += $data->quantity;
                $shipmentItem->save();

                // ensure shipment status at least allocated
                if ($shipment->status === 'draft') {
                    $shipment->status = 'allocated';
                    $shipment->save();
                }
            }

            $shipmentItem->refresh();
            $shipment->refresh();

            return [
                'movement' => $movement,
                'shipment_item' => $shipmentItem,
                'shipment' => $shipment,
            ];
        });
    }

    /**
     * @return array{shipment: Shipment, status_changed: bool}
     */
    public function dispatch(Shipment $shipment, string $idempotencyKey, CarbonImmutable $dispatchedAt, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($shipment, $dispatchedAt) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->status === 'delivered') {
                return [
                    'shipment' => $shipment,
                    'status_changed' => false,
                ];
            }

            if ($shipment->dispatched_at && $shipment->status === 'dispatched') {
                return [
                    'shipment' => $shipment,
                    'status_changed' => false,
                ];
            }

            $statusChanged = $shipment->status !== 'dispatched' || $shipment->dispatched_at === null;

            $shipment->status = 'dispatched';
            $shipment->dispatched_at = $shipment->dispatched_at ?? $dispatchedAt;
            $shipment->save();

            $outboundShipment = $shipment->outboundShipment;
            if ($outboundShipment) {
                $outboundShipment->update([
                    'status' => 'dispatched',
                    'dispatched_at' => $outboundShipment->dispatched_at ?? $dispatchedAt,
                ]);
            }

            $shipment->load(['items', 'driver', 'vehicle']);

            return [
                'shipment' => $shipment,
                'status_changed' => $statusChanged,
            ];
        });
    }

    /**
     * @return array{pod: Pod, shipment: Shipment, movements: array<int, StockMovement>, created: bool}
     */
    public function deliver(ShipmentPodData $data): array
    {
        return DB::transaction(function () use ($data) {
            /** @var Shipment $shipment */
            $shipment = Shipment::query()
                ->with(['items', 'outboundShipment.salesOrder'])
                ->lockForUpdate()
                ->findOrFail($data->shipmentId);

            $shipment->loadMissing('outboundShipment.salesOrder');
            $outboundShipment = $shipment->outboundShipment;
            $salesOrder = $outboundShipment?->salesOrder;
            if (! $outboundShipment || ! $salesOrder) {
                throw new StockException('Shipment is not linked to a sales order.');
            }

            $existingPod = $data->idempotencyKey
                ? Pod::query()->where('external_idempotency_key', $data->idempotencyKey)->first()
                : null;

            if ($existingPod && $existingPod->shipment_id !== $shipment->id) {
                throw new StockException('Idempotency key already used for another shipment.', 409);
            }

            /** @var Pod $pod */
            $pod = $shipment->proofOfDelivery()->updateOrCreate(
                ['shipment_id' => $shipment->id],
                [
                    'signed_by' => $data->signerName,
                    'signer_id' => $data->signerId,
                    'signed_at' => $data->signedAt,
                    'photo_path' => $data->photoPath,
                    'signature_path' => $data->signaturePath,
                    'notes' => $data->notes,
                    'meta' => $data->meta,
                    'external_idempotency_key' => $data->idempotencyKey,
                ]
            );

            $created = $pod->wasRecentlyCreated;
            $movements = [];

            foreach ($shipment->items as $item) {
                $remaining = $item->qty_picked - $item->qty_delivered;
                if ($remaining <= 0) {
                    continue;
                }

                $salesOrderItem = $item->salesOrderItem;
                $uom = $salesOrderItem ? $salesOrderItem->uom : 'PCS';

                $movement = $this->stockService->move(
                    type: 'deliver',
                    warehouseId: $salesOrder->warehouse_id,
                    itemId: $item->item_id,
                    lotId: $item->item_lot_id,
                    fromLocationId: $item->from_location_id,
                    toLocationId: null,
                    qty: $remaining,
                    uom: $uom,
                    refType: 'POD',
                    refId: sprintf('%d|%s', $item->id, $data->idempotencyKey),
                    actorUserId: $data->actorUserId,
                    movedAt: $data->signedAt,
                    remarks: $data->notes,
                );

                if ($movement->wasRecentlyCreated) {
                    $item->qty_delivered += $remaining;
                    $item->save();
                    $created = true;
                }

                $movements[] = $movement;
            }

            $shipment->status = 'delivered';
            $shipment->delivered_at = $shipment->delivered_at ?? $data->signedAt;
            $shipment->save();

            $updatedOutbound = $shipment->outboundShipment;
            if ($updatedOutbound) {
                $updatedOutbound->update([
                    'status' => 'delivered',
                    'delivered_at' => $updatedOutbound->delivered_at ?? $data->signedAt,
                ]);
            }

            $pod->refresh();
            $shipment->load('items');

            return [
                'pod' => $pod,
                'shipment' => $shipment,
                'movements' => $movements,
                'created' => $created,
            ];
        });
    }
}
