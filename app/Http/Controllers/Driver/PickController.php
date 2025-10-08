<?php

namespace App\Http\Controllers\Driver;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Http\Requests\Driver\DriverPickRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PickController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(DriverPickRequest $request): JsonResponse
    {
        /** @var ShipmentItem $shipmentItem */
        $shipmentItem = ShipmentItem::query()->with('shipment')->findOrFail($request->integer('shipment_item_id'));

        $this->authorizeDriver($shipmentItem->shipment);

        $idempotencyKey = $request->resolveIdempotencyKey(
            $shipmentItem->id,
            (float) $request->input('qty'),
            (string) $request->input('picked_at')
        );

        $dto = new PickLineData(
            shipmentItemId: $shipmentItem->id,
            quantity: (float) $request->input('qty'),
            idempotencyKey: $idempotencyKey,
            pickedAt: CarbonImmutable::parse($request->input('picked_at')),
            actorUserId: $request->user()?->id,
            remarks: $request->input('remarks')
        );

        try {
            $result = $this->service->completePick($dto);
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        $created = $result['movement']->wasRecentlyCreated;

        return response()->json([
            'data' => [
                'movement_id' => $result['movement']->id,
                'shipment_item' => $result['shipment_item'],
            ],
            'created' => $created,
        ], $created ? 201 : 200)->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    private function authorizeDriver(Shipment $shipment): void
    {
        if (! Gate::allows('driver-access-shipment', $shipment)) {
            abort(403, 'Unauthorized shipment.');
        }
    }
}
