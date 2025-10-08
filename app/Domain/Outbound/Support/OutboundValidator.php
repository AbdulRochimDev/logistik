<?php

namespace App\Domain\Outbound\Support;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\Location;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;

class OutboundValidator
{
    public function ensureAllocationContext(SoItem $soItem, Location $location, float $qty): void
    {
        $soItem->loadMissing('salesOrder.warehouse');
        $salesOrder = $soItem->salesOrder;

        if (! $salesOrder) {
            throw new StockException('Sales order not found for item.');
        }

        if ($location->warehouse_id !== $salesOrder->warehouse_id) {
            throw new StockException('Allocation location must be in the same warehouse as the sales order.');
        }

        $outstanding = $soItem->ordered_qty - $soItem->allocated_qty;
        if ($outstanding + 0.0001 < $qty) {
            throw new StockException('Allocation exceeds outstanding order quantity.');
        }
    }

    public function ensureStockAvailability(?float $available, float $qty): void
    {
        if (($available ?? 0.0) + 0.0001 < $qty) {
            throw new StockException('Insufficient available quantity to allocate.');
        }
    }

    public function ensurePickable(ShipmentItem $shipmentItem, float $qty): void
    {
        $shipment = $shipmentItem->shipment;

        if ($shipment && $shipment->status === 'delivered') {
            throw new StockException('Shipment already delivered.');
        }

        if ($shipmentItem->from_location_id === null) {
            throw new StockException('Shipment item missing pick location.');
        }

        $remaining = $shipmentItem->qty_planned - $shipmentItem->qty_picked;
        if ($qty > $remaining + 0.0001) {
            throw new StockException('Pick quantity exceeds planned amount.');
        }
    }

    public function ensureShipmentLinkedToSalesOrder(Shipment $shipment): void
    {
        $shipment->loadMissing('outboundShipment.salesOrder');
        $outboundShipment = $shipment->outboundShipment;
        $salesOrder = $outboundShipment?->salesOrder;

        if (! $outboundShipment || ! $salesOrder) {
            throw new StockException('Shipment is not linked to a sales order.');
        }
    }

    public function ensureDeliverable(Shipment $shipment): void
    {
        $this->ensureShipmentLinkedToSalesOrder($shipment);
    }

    public function ensureDispatchable(Shipment $shipment): void
    {
        if ($shipment->status === 'delivered') {
            throw new StockException('Shipment already delivered.');
        }
    }
}
