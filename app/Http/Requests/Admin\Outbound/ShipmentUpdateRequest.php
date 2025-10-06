<?php

namespace App\Http\Requests\Admin\Outbound;

use Illuminate\Foundation\Http\FormRequest;

class ShipmentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'outbound_shipment_id' => ['required', 'integer', 'exists:outbound_shipments,id'],
            'carrier' => ['nullable', 'string'],
            'tracking_no' => ['nullable', 'string'],
            'shipped_at' => ['nullable'],
            'departed_at' => ['nullable'],
            'delivered_at' => ['nullable'],
        ];
    }
}
