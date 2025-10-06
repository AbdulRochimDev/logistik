<?php

namespace App\Http\Requests\Admin\Inbound;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderStoreRequest extends FormRequest
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
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'po_no' => ['required', 'string', 'unique:purchase_orders,po_no'],
            'eta' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
