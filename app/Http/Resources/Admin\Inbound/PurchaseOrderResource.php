<?php

namespace App\Http\Resources\Admin\Inbound;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'po_no' => $this->po_no,
            'status' => $this->status,
            'eta' => $this->eta,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'poItems' => PoItemCollection::make($this->whenLoaded('poItems')),
            'inboundShipment' => InboundShipmentResource::make($this->whenLoaded('inboundShipment')),
        ];
    }
}
