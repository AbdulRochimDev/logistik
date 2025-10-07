<?php

namespace App\Domain\Outbound\Http\Requests;

use App\Domain\Outbound\Models\SoItem;
use Illuminate\Foundation\Http\FormRequest;

class AllocateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'so_item_id' => ['required', 'integer', 'exists:so_items,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'lot_no' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'remarks' => ['nullable', 'string'],
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
            $soItemId = $this->input('so_item_id');
            if (! $soItemId) {
                return;
            }

            /** @var SoItem|null $soItem */
            $soItem = SoItem::query()->with('item')->find($soItemId);
            if (! $soItem) {
                return;
            }

            $item = $soItem->item;
            if (! $item) {
                return;
            }

            if ($item->is_lot_tracked && ! $this->filled('lot_no')) {
                $validator->errors()->add('lot_no', 'Lot number is required for lot tracked items.');
            }
        });
    }
}
