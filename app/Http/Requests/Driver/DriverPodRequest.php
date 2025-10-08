<?php

namespace App\Http\Requests\Driver;

use App\Domain\Outbound\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class DriverPodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'signer_name' => ['required', 'string'],
            'signer_id' => ['nullable', 'string'],
            'signed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
            'device_id' => ['nullable', 'string'],
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
                ->with('proofOfDelivery')
                ->find($this->integer('shipment_id'));

            if (! $shipment) {
                return;
            }

            if (! in_array($shipment->status, ['dispatched', 'delivered'], true)) {
                $validator->errors()->add('shipment_id', 'Shipment harus dalam status dispatched sebelum PoD.');
            }

            $idempotencyKey = $this->resolveIdempotencyKey(
                $shipment->id,
                $this->string('signer_name')->toString(),
                (string) $this->input('signed_at')
            );

            $existingPod = $shipment->proofOfDelivery;

            if ($existingPod && $existingPod->external_idempotency_key !== $idempotencyKey) {
                throw new HttpResponseException(response()->json([
                    'message' => 'PoD sudah tercatat untuk shipment ini.',
                ], 409)->withHeaders([
                    'Idempotency-Key' => $idempotencyKey,
                ]));
            }
        });
    }

    public function resolveIdempotencyKey(int $shipmentId, string $signer, string $timestamp): string
    {
        $header = trim((string) $this->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('POD|%d|%s|%s', $shipmentId, $signer, $timestamp));
    }
}
