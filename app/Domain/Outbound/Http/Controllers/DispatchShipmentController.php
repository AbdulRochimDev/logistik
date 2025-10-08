<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\Http\Requests\DispatchShipmentRequest;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Services\OutboundService;
use App\Support\Idempotency\Exceptions\IdempotencyException;
use App\Support\Idempotency\IdempotencyManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class DispatchShipmentController
{
    public function __construct(
        private readonly OutboundService $service,
        private readonly IdempotencyManager $idempotency
    ) {}

    public function __invoke(DispatchShipmentRequest $request): JsonResponse
    {
        /** @var Shipment $shipment */
        $shipment = Shipment::query()->findOrFail($request->integer('shipment_id'));

        try {
            $idempotencyKey = $this->idempotency->resolve($request, 'outbound.dispatch', [$shipment->id]);
        } catch (IdempotencyException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        try {
            $result = $this->service->dispatch(
                shipment: $shipment,
                idempotencyKey: $idempotencyKey,
                dispatchedAt: CarbonImmutable::parse($request->input('dispatched_at')),
                actorUserId: $request->user()?->id
            );
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'data' => $result['shipment'],
        ], $result['status_changed'] ? 201 : 200);
    }

}
