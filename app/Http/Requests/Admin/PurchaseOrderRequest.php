<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $purchaseOrderId = $this->route('purchase_order')?->id;

        return [
            'po_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('purchase_orders', 'po_no')->ignore($purchaseOrderId),
            ],
            'status' => ['required', Rule::in(['draft', 'approved', 'closed'])],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'eta' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer', 'exists:po_items,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.uom' => ['required', 'string', 'max:20'],
            'lines.*.qty_ordered' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('lines')) {
            $lines = collect($this->input('lines'))
                ->map(function (array $line) {
                    $line['qty_ordered'] = isset($line['qty_ordered'])
                        ? (float) $line['qty_ordered']
                        : $line['qty_ordered'];

                    return $line;
                })
                ->all();

            $this->merge(['lines' => $lines]);
        }
    }
}
