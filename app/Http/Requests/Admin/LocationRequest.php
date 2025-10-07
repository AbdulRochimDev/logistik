<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locationId = $this->route('location')?->id;
        $warehouseId = $this->input('warehouse_id')
            ?? $this->route('location')?->warehouse_id;

        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('locations', 'code')
                    ->ignore($locationId)
                    ->where(fn ($query) => $query->where('warehouse_id', $warehouseId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
