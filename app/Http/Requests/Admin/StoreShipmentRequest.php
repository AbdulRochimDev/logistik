<?php

namespace App\Http\Requests\Admin;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Outbound\Models\OutboundShipment;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin_gudang') ?? false;
    }

    public function rules(): array
    {
        return [
            'outbound_shipment_id' => ['required', 'integer', 'exists:outbound_shipments,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'planned_at' => ['nullable', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.item_lot_id' => ['nullable', 'integer', 'exists:item_lots,id'],
            'lines.*.from_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'lines.*.qty_planned' => ['required', 'numeric', 'gte:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('lines')) {
            $lines = collect($this->input('lines'))
                ->map(function (array $line) {
                    if (isset($line['qty_planned'])) {
                        $line['qty_planned'] = (float) $line['qty_planned'];
                    }

                    return $line;
                })
                ->values()
                ->all();

            $this->merge(['lines' => $lines]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $lines = collect($this->input('lines', []));
            if ($lines->isEmpty()) {
                return;
            }

            $itemMap = Item::query()
                ->whereIn('id', $lines->pluck('item_id')->filter()->unique())
                ->get(['id', 'is_lot_tracked'])
                ->keyBy('id');

            $lotMap = ItemLot::query()
                ->whereIn('id', $lines->pluck('item_lot_id')->filter()->unique())
                ->get(['id', 'item_id'])
                ->keyBy('id');

            $locationMap = Location::query()
                ->whereIn('id', $lines->pluck('from_location_id')->filter()->unique())
                ->get(['id', 'warehouse_id'])
                ->keyBy('id');

            $warehouseId = (int) $this->input('warehouse_id');
            $outbound = $this->input('outbound_shipment_id')
                ? OutboundShipment::with('salesOrder')->find($this->input('outbound_shipment_id'))
                : null;

            if ($outbound && $outbound->salesOrder && (int) $outbound->salesOrder->warehouse_id !== $warehouseId) {
                $validator->errors()->add('outbound_shipment_id', 'Outbound shipment harus berasal dari gudang yang sama.');
            }

            foreach ($lines as $index => $line) {
                $item = $itemMap->get($line['item_id'] ?? null);

                if ($item && $item->is_lot_tracked && empty($line['item_lot_id'])) {
                    $validator->errors()->add("lines.$index.item_lot_id", 'Lot number is required for lot-tracked items.');
                }

                if (! empty($line['item_lot_id'])) {
                    $lot = $lotMap->get($line['item_lot_id']);
                    if (! $lot || (int) $lot->item_id !== (int) ($line['item_id'] ?? 0)) {
                        $validator->errors()->add("lines.$index.item_lot_id", 'Lot does not belong to the selected item.');
                    }
                }

                if (! empty($line['from_location_id'])) {
                    $location = $locationMap->get($line['from_location_id']);
                    if ($location && (int) $location->warehouse_id !== $warehouseId) {
                        $validator->errors()->add("lines.$index.from_location_id", 'Location must belong to selected warehouse.');
                    }
                }
            }
        });
    }
}
