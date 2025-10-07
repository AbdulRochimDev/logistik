<?php

namespace App\Domain\Outbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliverShipmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'signed_by' => ['required', 'string'],
            'signer_id' => ['nullable', 'string'],
            'signed_at' => ['required', 'date'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'photo_path' => ['nullable', 'string'],
            'signature_path' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
