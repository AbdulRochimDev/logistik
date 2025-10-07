<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\Vehicle;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    $this->warehouse = Warehouse::where('code', 'WH1')->firstOrFail();
    $this->lotItem = Item::where('sku', 'SKU-LOT-01')->firstOrFail();
    $this->stdItem = Item::where('sku', 'SKU-STD-02')->firstOrFail();
    $this->lot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();
    $this->rackA1 = Location::where('code', 'RACK-A1')->firstOrFail();
    $this->outboundShipment = OutboundShipment::firstOrFail();
});

test('shipment validation rejects negative quantity and missing fields', function (): void {
    $payload = [
        'outbound_shipment_id' => $this->outboundShipment->id,
        'warehouse_id' => $this->warehouse->id,
        'planned_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'lines' => [
            [
                'item_id' => $this->lotItem->id,
                'item_lot_id' => $this->lot->id,
                'from_location_id' => $this->rackA1->id,
                'qty_planned' => -1,
            ],
        ],
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('admin.shipments.store'), $payload);

    $response->assertSessionHasErrors(['lines.0.qty_planned']);

    $responseMissingFields = $this->actingAs($this->admin)
        ->post(route('admin.shipments.store'), [
            'outbound_shipment_id' => $this->outboundShipment->id,
            'warehouse_id' => null,
            'lines' => [],
        ]);

    $responseMissingFields->assertSessionHasErrors(['warehouse_id', 'lines']);
});

test('cannot assign driver or vehicle on delivered shipment', function (): void {
    $driver = Driver::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['status' => 'active']);

    $shipment = Shipment::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'delivered',
    ]);

    $payload = [
        'outbound_shipment_id' => $this->outboundShipment->id,
        'warehouse_id' => $this->warehouse->id,
        'driver_id' => Driver::factory()->create(['status' => 'active'])->id,
        'vehicle_id' => Vehicle::factory()->create(['status' => 'active'])->id,
        'planned_at' => now()->format('Y-m-d\TH:i'),
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('admin.shipments.update', $shipment), $payload);

    $response->assertSessionHasErrors(['update']);
});
