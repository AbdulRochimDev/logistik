<?php

namespace App\Http\Requests\Admin\Inbound;

use Illuminate\Foundation\Http\FormRequest;

class GrnStoreRequest extends FormRequest
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
            'inbound_shipment_id' => ['required'],
            'received_at' => ['required'],
            'status' => ['required'],
        ];
    }
}
