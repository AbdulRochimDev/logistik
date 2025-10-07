<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Outbound\Http\Requests\AllocateRequest;
use App\Domain\Outbound\Models\SoItem;
use App\Domain\Outbound\Services\OutboundService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllocateController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(AllocateRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (! $userId) {
            throw new StockException('Authentication required.', 401);
        }

        /** @var SoItem $soItem */
        $soItem = SoItem::query()->with('item', 'salesOrder.warehouse')->findOrFail($request->integer('so_item_id'));
        /** @var Location $location */
        $location = Location::query()->findOrFail($request->integer('location_id'));

        $itemLot = null;
        $lotNo = trim((string) $request->input('lot_no', ''));
        if ($soItem->item->is_lot_tracked) {
            $itemLot = ItemLot::query()
                ->where('item_id', $soItem->item_id)
                ->where('lot_no', $lotNo)
                ->first();

            if (! $itemLot) {
                throw new StockException('Lot not found for allocation.');
            }
        } elseif ($lotNo !== '') {
            $itemLot = ItemLot::query()
                ->where('item_id', $soItem->item_id)
                ->where('lot_no', $lotNo)
                ->first();
        }

        $qty = (float) $request->input('qty');

        $key = $this->resolveIdempotencyKey($request, $soItem->id, $location->id, $qty, $lotNo);

        try {
            $result = $this->service->allocate(
                soItem: $soItem,
                qty: $qty,
                location: $location,
                itemLot: $itemLot,
                idempotencyKey: $key,
                actorUserId: $userId,
                allocatedAt: CarbonImmutable::now(),
                remarks: $request->input('remarks')
            );
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'data' => [
                'movement_id' => $result['movement']->id,
                'stock_id' => $result['movement']->stock_id,
                'idempotency_key' => $key,
                'so_item' => $result['so_item'],
            ],
        ], $result['movement']->wasRecentlyCreated ? 201 : 200);
    }

    private function resolveIdempotencyKey(Request $request, int $soItemId, int $locationId, float $qty, string $lotNo): string
    {
        $headerKey = $request->headers->get('X-Idempotency-Key');
        if ($headerKey) {
            return trim($headerKey);
        }

        $provided = trim((string) $request->input('idempotency_key', ''));
        if ($provided !== '') {
            return $provided;
        }

        $payload = implode('|', ['ALLOC', $soItemId, $locationId, $qty, $lotNo]);

        return 'ALLOC|'.hash('sha256', $payload);
    }
}
