<?php

namespace App\Domain\Outbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchShipmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'dispatched_at' => ['required', 'date'],
            'carrier' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
