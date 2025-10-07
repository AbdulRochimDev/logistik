<?php

namespace App\Domain\Inbound\Http\Requests;

use App\Domain\Inventory\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class PostGrnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'grn_header_id' => ['nullable', 'integer', 'exists:grn_headers,id'],
            'inbound_shipment_id' => ['required', 'integer', 'exists:inbound_shipments,id'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.po_item_id' => ['required', 'integer', 'exists:po_items,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.to_location_id' => ['required', 'integer', 'exists:locations,id'],
            'lines.*.lot_no' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('lines')) {
            $lines = collect($this->input('lines'))
                ->map(function ($line) {
                    $line['qty'] = isset($line['qty']) ? (float) $line['qty'] : $line['qty'];

                    return $line;
                });

            $this->merge(['lines' => $lines->all()]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lineItems = collect($this->input('lines'));
            if ($lineItems->isEmpty()) {
                return;
            }

            $itemMap = Item::query()
                ->whereIn('id', $lineItems->pluck('item_id')->unique()->all())
                ->get(['id', 'is_lot_tracked'])
                ->keyBy('id');

            foreach ($lineItems as $index => $line) {
                $item = $itemMap->get($line['item_id'] ?? null);

                if ($item && $item->is_lot_tracked && empty($line['lot_no'])) {
                    $validator->errors()->add("lines.$index.lot_no", 'Lot number is required for lot-tracked items.');
                }
            }

            $inboundId = $this->input('inbound_shipment_id');
            $locationIds = $lineItems->pluck('to_location_id')->unique()->all();
            if (! $inboundId || empty($locationIds)) {
                return;
            }

            $inbound = \App\Domain\Inbound\Models\InboundShipment::query()
                ->with('purchaseOrder')
                ->find($inboundId);

            if (! $inbound) {
                return;
            }

            /** @var \Illuminate\Support\Collection<int, \App\Domain\Inventory\Models\Location> $locations */
            $locations = \App\Domain\Inventory\Models\Location::query()
                ->whereIn('id', $locationIds)
                ->get(['id', 'warehouse_id'])
                ->keyBy('id');

            foreach ($lineItems as $index => $line) {
                $location = $locations[$line['to_location_id'] ?? null] ?? null;
                if ($location && $location->warehouse_id !== $inbound->purchaseOrder?->warehouse_id) {
                    $validator->errors()->add("lines.$index.to_location_id", 'Location must belong to the inbound warehouse.');
                }
            }
        });
    }
}
