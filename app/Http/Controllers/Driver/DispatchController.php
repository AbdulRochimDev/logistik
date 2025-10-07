<?php

namespace App\Http\Controllers\Driver;

use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Services\OutboundService;
use App\Http\Requests\Driver\DriverDispatchRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DispatchController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(DriverDispatchRequest $request): JsonResponse
    {
        $shipment = Shipment::query()->findOrFail($request->integer('shipment_id'));

        $this->authorizeDriver($shipment);

        $idempotencyKey = $this->resolveIdempotencyKey($request, $shipment->id);

        $result = $this->service->dispatch(
            shipment: $shipment,
            idempotencyKey: $idempotencyKey,
            dispatchedAt: CarbonImmutable::parse($request->input('dispatched_at')),
            actorUserId: $request->user()?->id
        );

        return response()->json([
            'data' => $result['shipment'],
        ], $result['status_changed'] ? 201 : 200);
    }

    private function authorizeDriver(Shipment $shipment): void
    {
        if (! Gate::allows('driver-access-shipment', $shipment)) {
            abort(403, 'Unauthorized shipment.');
        }
    }

    private function resolveIdempotencyKey(DriverDispatchRequest $request, int $shipmentId): string
    {
        $header = trim((string) $request->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('DISPATCH|%d', $shipmentId));
    }
}
