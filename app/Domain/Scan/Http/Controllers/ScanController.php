<?php

namespace App\Domain\Scan\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Services\StockService;
use App\Support\Idempotency\Exceptions\IdempotencyException;
use App\Support\Idempotency\IdempotencyManager;
use App\Domain\Scan\Http\Requests\ScanRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ScanController
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly IdempotencyManager $idempotency
    ) {}

    public function __invoke(ScanRequest $request): JsonResponse
    {
        $sku = trim((string) $request->input('sku'));
        $locationCode = trim((string) $request->input('location'));

        $item = Item::where('sku', $sku)->firstOrFail();
        $location = Location::where('code', $locationCode)->firstOrFail();
        $qty = (float) $request->input('qty');
        $direction = strtolower((string) $request->input('direction'));
        $lotNo = trim((string) $request->input('lot_no', ''));

        $itemLot = null;
        if ($item->is_lot_tracked) {
            $itemLot = ItemLot::query()
                ->firstOrCreate(
                    ['item_id' => $item->id, 'lot_no' => $lotNo],
                    []
                );
        } elseif ($lotNo !== '') {
            $itemLot = ItemLot::query()
                ->where('item_id', $item->id)
                ->where('lot_no', $lotNo)
                ->first();
        }

        $movementType = $direction === 'in' ? 'inbound_putaway' : 'pick';
        $fromLocation = $direction === 'out' ? $location->id : null;
        $toLocation = $direction === 'in' ? $location->id : null;

        try {
            $idempotencyKey = $this->idempotency->resolve($request, 'scan.mutation', [
                $request->input('device_id'),
                $request->input('ts'),
                $request->input('sku'),
                $request->input('qty'),
                $request->input('direction'),
                $request->input('location'),
                $lotNo,
            ]);
        } catch (IdempotencyException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
        $refId = sprintf('%s|%s', Str::upper($direction), $idempotencyKey);

        try {
            $movement = $this->stockService->move(
                type: $movementType,
                warehouseId: $location->warehouse_id,
                itemId: $item->id,
                lotId: $itemLot?->id,
                fromLocationId: $fromLocation,
                toLocationId: $toLocation,
                qty: $qty,
                uom: $item->default_uom,
                refType: 'SCAN',
                refId: $refId,
                actorUserId: $request->user()?->id,
                movedAt: CarbonImmutable::parse($request->input('ts')),
                remarks: sprintf('Scan %s via %s', $direction, (string) $request->input('device_id'))
            );
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'data' => [
                'movement_id' => $movement->id,
                'type' => $movement->type,
                'stock_id' => $movement->stock_id,
                'idempotency_key' => $idempotencyKey,
                'applied' => $movement->wasRecentlyCreated,
            ],
        ], $movement->wasRecentlyCreated ? 201 : 200);
    }

}
