<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin_gudang') ?? false;
    }

    public function rules(): array
    {
        return [
            'plate_no' => ['required', 'string', 'max:50', 'unique:vehicles,plate_no'],
            'type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'numeric', 'gte:0'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
