<?php

namespace App\Http\Requests\Admin\Outbound;

use Illuminate\Foundation\Http\FormRequest;

class SalesOrderStoreRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'so_no' => ['required', 'string', 'unique:sales_orders,so_no'],
            'ship_by' => ['nullable', 'date'],
        ];
    }
}
