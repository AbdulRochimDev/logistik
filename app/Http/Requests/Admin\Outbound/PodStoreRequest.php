<?php

namespace App\Http\Requests\Admin\Outbound;

use Illuminate\Foundation\Http\FormRequest;

class PodStoreRequest extends FormRequest
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
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'signed_at' => ['required'],
            'signed_by' => ['required', 'string'],
        ];
    }
}
