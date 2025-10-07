<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

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
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
