<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentStoreRequest extends FormRequest
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
            'assignment_no' => ['required', 'string', 'unique:driver_assignments,assignment_no'],
            'driver_profile_id' => ['required', 'integer', 'exists:driver_profiles,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'outbound_shipment_id' => ['required', 'integer', 'exists:outbound_shipments,id'],
            'assigned_at' => ['required'],
            'status' => ['required', 'in:assigned,en_route,delivered,closed'],
            'completed_at' => ['nullable'],
        ];
    }
}
