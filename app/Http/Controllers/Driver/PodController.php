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

        $idempotencyKey = $this->resolveIdempotencyKey(
            $request,
            $shipment->id,
            $request->string('signer_name')->toString(),
            $request->input('signed_at')
        );

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $disk = config('filesystems.default', 'local');
            $photoPath = Storage::disk($disk)->putFile('pods/photos', $request->file('photo'));
        }

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

    private function authorizeDriver(Shipment $shipment): void
    {
        if (! Gate::allows('driver-access-shipment', $shipment)) {
            abort(403, 'Unauthorized shipment.');
        }
    }

    private function resolveIdempotencyKey(DriverPodRequest $request, int $shipmentId, string $signer, string $timestamp): string
    {
        $header = trim((string) $request->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('POD|%d|%s|%s', $shipmentId, $signer, $timestamp));
    }
}
