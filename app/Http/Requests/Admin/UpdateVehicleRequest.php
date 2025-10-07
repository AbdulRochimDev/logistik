<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin_gudang') ?? false;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'plate_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'plate_no')->ignore($vehicleId),
            ],
            'type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'numeric', 'gte:0'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
