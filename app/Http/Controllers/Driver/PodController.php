<?php

namespace App\Http\Controllers\Driver;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Services\OutboundService;
use App\Http\Requests\Driver\DriverPodRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PodController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(DriverPodRequest $request): JsonResponse
    {
        $shipment = Shipment::query()->findOrFail($request->integer('shipment_id'));

        $this->authorizeDriver($shipment);

        $shipment->loadMissing('proofOfDelivery');

        $idempotencyKey = $request->resolveIdempotencyKey(
            $shipment->id,
            $request->string('signer_name')->toString(),
            (string) $request->input('signed_at')
        );

        $existingPod = $shipment->proofOfDelivery;
        $isReplay = $existingPod && $existingPod->external_idempotency_key === $idempotencyKey;

        if ($existingPod && ! $isReplay) {
            return response()->json([
                'message' => 'PoD sudah tercatat untuk shipment ini.',
            ], 409)->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ]);
        }

        $podDisk = config('wms.storage.pod_disk', config('filesystems.default', 's3'));
        $photoPath = $existingPod?->photo_path;

        if (! $isReplay && $request->hasFile('photo')) {
            $photoPath = Storage::disk($podDisk)->putFile('pods/photos', $request->file('photo'), ['visibility' => 'private']);
        }

        $meta = $request->input('meta', []);
        $meta = is_array($meta) ? $meta : [];
        $meta = array_filter(array_merge($meta, [
            'user_agent' => $request->userAgent(),
            'device_id' => $request->input('device_id'),
        ]), static fn ($value) => $value !== null && $value !== '');

        $dto = new ShipmentPodData(
            shipmentId: $shipment->id,
            signerName: $request->string('signer_name')->toString(),
            signedAt: CarbonImmutable::parse($request->input('signed_at')),
            idempotencyKey: $idempotencyKey,
            actorUserId: $request->user()?->id,
            signerId: $request->input('signer_id'),
            photoPath: $photoPath,
            signaturePath: null,
            notes: $request->input('notes'),
            meta: $meta,
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
            'created' => $result['created'],
            'replayed' => $result['replayed'],
        ], $result['created'] ? 201 : 200)->withHeaders([
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
