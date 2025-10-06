<?php

namespace App\Http\Requests\Admin\Outbound;

use Illuminate\Foundation\Http\FormRequest;

class PickingStoreRequest extends FormRequest
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
            'pick_list_id' => ['required'],
            'picked_lines' => ['required'],
        ];
    }
}
