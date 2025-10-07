<?php

namespace App\Domain\Scan\Http\Requests;

use App\Domain\Inventory\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class ScanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'direction' => ['required', 'in:in,out'],
            'location' => ['required', 'string'],
            'ts' => ['required', 'date'],
            'device_id' => ['required', 'string'],
            'lot_no' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('qty')) {
            $this->merge(['qty' => (float) $this->input('qty')]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $sku = trim((string) $this->input('sku', ''));
            if ($sku === '') {
                return;
            }

            /** @var Item|null $item */
            $item = Item::query()->where('sku', $sku)->first();
            if (! $item) {
                $validator->errors()->add('sku', 'Item not found for scan.');

                return;
            }

            if ($item->is_lot_tracked && ! $this->filled('lot_no')) {
                $validator->errors()->add('lot_no', 'Lot number required for lot tracked item.');
            }
        });
    }
}
