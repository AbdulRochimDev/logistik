<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Http\Requests\DeliverShipmentRequest;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Services\OutboundService;
use App\Support\Idempotency\Exceptions\IdempotencyException;
use App\Support\Idempotency\IdempotencyManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class DeliverShipmentController
{
    public function __construct(
        private readonly OutboundService $service,
        private readonly IdempotencyManager $idempotency
    ) {}

    public function __invoke(DeliverShipmentRequest $request): JsonResponse
    {
        /** @var Shipment $shipment */
        $shipment = Shipment::query()->findOrFail($request->integer('shipment_id'));

        try {
            $idempotencyKey = $this->idempotency->resolve($request, 'outbound.pod', [
                $shipment->id,
                $request->string('signed_by')->toString(),
                $request->input('signed_at'),
            ]);
        } catch (IdempotencyException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
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

}
