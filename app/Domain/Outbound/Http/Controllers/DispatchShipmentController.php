<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\Http\Requests\DispatchShipmentRequest;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Services\OutboundService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispatchShipmentController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(DispatchShipmentRequest $request): JsonResponse
    {
        /** @var Shipment $shipment */
        $shipment = Shipment::query()->findOrFail($request->integer('shipment_id'));

        $idempotencyKey = $this->resolveIdempotencyKey($request, $shipment->id);

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

    private function resolveIdempotencyKey(Request $request, int $shipmentId): string
    {
        $header = trim((string) $request->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('DISPATCH|%d', $shipmentId));
    }
}
