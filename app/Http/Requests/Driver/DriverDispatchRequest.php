<?php

namespace App\Http\Requests\Driver;

use App\Domain\Outbound\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class DriverDispatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'dispatched_at' => ['required', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->fails()) {
                return;
            }

            /** @var Shipment|null $shipment */
            $shipment = Shipment::query()
                ->with(['driver', 'vehicle'])
                ->find($this->integer('shipment_id'));

            if (! $shipment) {
                return;
            }

            $idempotencyKey = $this->resolveIdempotencyKey($shipment->id);

            if ($shipment->status === 'delivered') {
                throw new HttpResponseException(response()->json([
                    'message' => 'Shipment sudah delivered.',
                ], 409)->withHeaders([
                    'Idempotency-Key' => $idempotencyKey,
                ]));
            }

            if (! in_array($shipment->status, ['draft', 'allocated', 'dispatched'], true)) {
                $validator->errors()->add('shipment_id', 'Shipment tidak dapat di-dispatch dari status saat ini.');
            }

            if (! $shipment->driver_id || ! $shipment->driver || $shipment->driver->status !== 'active') {
                $validator->errors()->add('shipment_id', 'Driver tidak aktif untuk shipment ini.');
            }

            if (! $shipment->vehicle_id || ! $shipment->vehicle || $shipment->vehicle->status !== 'active') {
                $validator->errors()->add('shipment_id', 'Kendaraan tidak aktif untuk shipment ini.');
            }
        });
    }

    public function resolveIdempotencyKey(int $shipmentId): string
    {
        $header = trim((string) $this->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('DISPATCH|%d', $shipmentId));
    }
}
