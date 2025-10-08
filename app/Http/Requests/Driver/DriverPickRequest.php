<?php

namespace App\Http\Requests\Driver;

use App\Domain\Outbound\Models\ShipmentItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DriverPickRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipment_item_id' => ['required', 'integer', 'exists:shipment_items,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'picked_at' => ['required', 'date'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->fails()) {
                return;
            }

            /** @var ShipmentItem|null $shipmentItem */
            $shipmentItem = ShipmentItem::query()
                ->with('shipment')
                ->find($this->integer('shipment_item_id'));

            if (! $shipmentItem) {
                return;
            }

            $shipment = $shipmentItem->shipment;
            if ($shipment && $shipment->status === 'delivered') {
                $validator->errors()->add('shipment_item_id', 'Shipment sudah delivered.');

                return;
            }

            $remaining = (float) $shipmentItem->qty_planned - (float) $shipmentItem->qty_picked;
            $quantity = (float) $this->input('qty');

            if ($quantity > $remaining + 0.0001) {
                $validator->errors()->add('qty', 'Qty pick melebihi sisa rencana.');
            }
        });
    }

    public function resolveIdempotencyKey(int $shipmentItemId, float $quantity, string $timestamp): string
    {
        $header = trim((string) $this->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('PICK|%d|%s|%s', $shipmentItemId, $quantity, $timestamp));
    }
}
