<?php

namespace App\Domain\Outbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompletePickRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipment_item_id' => ['required', 'integer', 'exists:shipment_items,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'picked_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('qty')) {
            $this->merge(['qty' => (float) $this->input('qty')]);
        }
    }
}
