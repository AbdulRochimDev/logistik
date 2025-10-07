<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
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
    $this->rackA2 = Location::where('code', 'RACK-A2')->firstOrFail();
    $this->outboundShipment = OutboundShipment::firstOrFail();
});

test('admin can manage shipment lifecycle with guards and idempotent actions', function (): void {
    // create shipment draft without assignments and delete before dispatch
    $draftPayload = [
        'outbound_shipment_id' => $this->outboundShipment->id,
        'warehouse_id' => $this->warehouse->id,
        'planned_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'lines' => [
            [
                'item_id' => $this->lotItem->id,
                'item_lot_id' => $this->lot->id,
                'from_location_id' => $this->rackA1->id,
                'qty_planned' => 2,
            ],
            [
                'item_id' => $this->stdItem->id,
                'item_lot_id' => null,
                'from_location_id' => $this->rackA2->id,
                'qty_planned' => 5,
            ],
        ],
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.shipments.store'), $draftPayload)
        ->assertRedirect();

    $firstShipment = Shipment::latest('id')->firstOrFail();
    expect($firstShipment->status)->toBe('draft');

    $this->actingAs($this->admin)
        ->delete(route('admin.shipments.destroy', $firstShipment))
        ->assertRedirect(route('admin.shipments.index'));

    expect(Shipment::whereKey($firstShipment->id)->exists())->toBeFalse();

    // create second shipment with driver & vehicle assignments
    $driver = Driver::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['status' => 'active']);

    $createPayload = [
        'outbound_shipment_id' => $this->outboundShipment->id,
        'warehouse_id' => $this->warehouse->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'planned_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'lines' => [
            [
                'item_id' => $this->lotItem->id,
                'item_lot_id' => $this->lot->id,
                'from_location_id' => $this->rackA1->id,
                'qty_planned' => 4,
            ],
            [
                'item_id' => $this->stdItem->id,
                'item_lot_id' => null,
                'from_location_id' => $this->rackA2->id,
                'qty_planned' => 6,
            ],
        ],
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.shipments.store'), $createPayload)
        ->assertRedirect();

    $shipment = Shipment::latest('id')->firstOrFail();
    expect($shipment->status)->toBe('draft');
    expect($shipment->items)->toHaveCount(2);

    // update shipment quantities
    $shipment->refresh();
    $lines = $shipment->items->map(fn (ShipmentItem $item) => [
        'id' => $item->id,
        'item_id' => $item->item_id,
        'item_lot_id' => $item->item_lot_id,
        'from_location_id' => $item->from_location_id,
        'qty_planned' => $item->item_id === $this->lotItem->id ? 5 : 7,
    ])->all();

    $updatePayload = [
        'outbound_shipment_id' => $this->outboundShipment->id,
        'warehouse_id' => $this->warehouse->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'planned_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        'lines' => $lines,
    ];

    $this->actingAs($this->admin)
        ->put(route('admin.shipments.update', $shipment), $updatePayload)
        ->assertRedirect(route('admin.shipments.show', $shipment));

    $shipment->refresh();
    expect((float) $shipment->items()->where('item_id', $this->lotItem->id)->value('qty_planned'))->toBe(5.0);

    // dispatch via admin quick action
    $this->actingAs($this->admin)
        ->post(route('admin.shipments.dispatch', $shipment))
        ->assertRedirect(route('admin.shipments.show', $shipment));

    $shipment->refresh();
    expect($shipment->status)->toBe('dispatched');

    // editing lines after dispatch should be blocked
    $response = $this->actingAs($this->admin)
        ->put(route('admin.shipments.update', $shipment), $updatePayload);
    $response->assertRedirect(route('admin.shipments.show', $shipment));
    $response->assertSessionHasErrors(['update']);

    // driver & vehicle deletion guarded while shipment active
    $this->actingAs($this->admin)
        ->delete(route('admin.drivers.destroy', $driver))
        ->assertRedirect(route('admin.drivers.index'))
        ->assertSessionHasErrors(['delete']);

    $this->actingAs($this->admin)
        ->delete(route('admin.vehicles.destroy', $vehicle))
        ->assertRedirect(route('admin.vehicles.index'))
        ->assertSessionHasErrors(['delete']);

    // mark delivered and ensure idempotent replay
    $this->actingAs($this->admin)
        ->post(route('admin.shipments.deliver', $shipment))
        ->assertRedirect(route('admin.shipments.show', $shipment));

    $shipment->refresh();
    expect($shipment->status)->toBe('delivered');

    $secondDeliver = $this->actingAs($this->admin)
        ->post(route('admin.shipments.deliver', $shipment));
    $secondDeliver->assertRedirect(route('admin.shipments.show', $shipment));
    $secondDeliver->assertSessionHas('status', 'Shipment sudah delivered.');
});
