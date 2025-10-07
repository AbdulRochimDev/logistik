<?php

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\Vehicle;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('driver and vehicle deletion guarded by active shipment status', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $warehouse = Warehouse::where('code', 'WH1')->firstOrFail();

    $driver = Driver::factory()->create(['status' => 'active']);
    $vehicle = Vehicle::factory()->create(['status' => 'active']);

    $shipment = Shipment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'allocated',
    ]);

    // block deletion while shipment still active
    $this->actingAs($admin)
        ->delete(route('admin.drivers.destroy', $driver))
        ->assertRedirect(route('admin.drivers.index'))
        ->assertSessionHasErrors(['delete']);

    $this->actingAs($admin)
        ->delete(route('admin.vehicles.destroy', $vehicle))
        ->assertRedirect(route('admin.vehicles.index'))
        ->assertSessionHasErrors(['delete']);

    // mark shipment delivered and allow deletion
    $shipment->update(['status' => 'delivered']);

    $this->actingAs($admin)
        ->delete(route('admin.drivers.destroy', $driver))
        ->assertRedirect(route('admin.drivers.index'))
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->delete(route('admin.vehicles.destroy', $vehicle))
        ->assertRedirect(route('admin.vehicles.index'))
        ->assertSessionHasNoErrors();

    expect(Driver::whereKey($driver->id)->exists())->toBeFalse();
    expect(Vehicle::whereKey($vehicle->id)->exists())->toBeFalse();
});
