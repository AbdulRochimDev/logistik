<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\Http\Requests\CompletePickRequest;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Support\Idempotency\Exceptions\IdempotencyException;
use App\Support\Idempotency\IdempotencyManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class CompletePickController
{
    public function __construct(
        private readonly OutboundService $service,
        private readonly IdempotencyManager $idempotency
    ) {}

    public function __invoke(CompletePickRequest $request): JsonResponse
    {
        /** @var ShipmentItem $shipmentItem */
        $shipmentItem = ShipmentItem::query()
            ->with('shipment')
            ->findOrFail($request->integer('shipment_item_id'));

        try {
            $idempotencyKey = $this->idempotency->resolve($request, 'outbound.pick', [
                $shipmentItem->id,
                $request->input('qty'),
                $request->input('picked_at'),
            ]);
        } catch (IdempotencyException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

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

        return response()->json([
            'data' => [
                'movement_id' => $result['movement']->id,
                'shipment_item' => $result['shipment_item'],
                'shipment' => $result['shipment'],
            ],
        ], $result['movement']->wasRecentlyCreated ? 201 : 200);
    }

}
