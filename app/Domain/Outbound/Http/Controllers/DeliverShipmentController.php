<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Http\Requests\DeliverShipmentRequest;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Services\OutboundService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliverShipmentController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(DeliverShipmentRequest $request): JsonResponse
    {
        /** @var Shipment $shipment */
        $shipment = Shipment::query()->findOrFail($request->integer('shipment_id'));

        $idempotencyKey = $request->input('idempotency_key');
        if (! $idempotencyKey) {
            $idempotencyKey = $this->resolveIdempotencyKey(
                $request,
                $shipment->id,
                $request->string('signed_by')->toString(),
                $request->input('signed_at')
            );
        }

        $dto = new ShipmentPodData(
            shipmentId: $shipment->id,
            signerName: $request->string('signed_by')->toString(),
            signedAt: CarbonImmutable::parse($request->input('signed_at')),
            idempotencyKey: $idempotencyKey,
            actorUserId: $request->user()?->id,
            signerId: $request->input('signer_id'),
            photoPath: $request->input('photo_path'),
            signaturePath: $request->input('signature_path'),
            notes: $request->input('notes'),
            meta: $request->input('meta'),
        );

        try {
            $result = $this->service->deliver($dto);
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'data' => $result,
        ], $result['created'] ? 201 : 200);
    }

    private function resolveIdempotencyKey(Request $request, int $shipmentId, string $signedBy, string $timestamp): string
    {
        $header = trim((string) $request->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('POD|%d|%s|%s', $shipmentId, $signedBy, $timestamp));
    }
}
