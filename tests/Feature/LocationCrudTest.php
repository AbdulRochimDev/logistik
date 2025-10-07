<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\Warehouse;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AdminUserSeeder::class);
    $this->seed(LogisticsDemoSeeder::class);
    $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
});

test('admin can create and update location', function () {
    $warehouse = Warehouse::firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('admin.locations.store'), [
            'warehouse_id' => $warehouse->id,
            'code' => 'PICK-A3',
            'name' => 'Rack A3',
            'type' => 'pick',
            'is_default' => false,
        ])
        ->assertRedirect(route('admin.locations.index'));

    $location = Location::where('code', 'PICK-A3')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.locations.update', $location), [
            'warehouse_id' => $warehouse->id,
            'code' => 'PICK-A3',
            'name' => 'Rack A3 Updated',
            'type' => 'pick',
            'is_default' => true,
        ])
        ->assertRedirect(route('admin.locations.index'));

    expect($location->fresh()->name)->toBe('Rack A3 Updated')
        ->and($location->fresh()->is_default)->toBeTrue();
});

test('location deletion blocked when stock exists', function () {
    $location = Location::where('code', 'RACK-A1')->firstOrFail();

    $item = Item::where('sku', 'SKU-STD-02')->firstOrFail();

    Stock::query()->updateOrCreate([
        'warehouse_id' => $location->warehouse_id,
        'location_id' => $location->id,
        'item_id' => $item->id,
        'item_lot_id' => null,
    ], [
        'qty_on_hand' => 5,
        'qty_allocated' => 0,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.locations.destroy', $location))
        ->assertRedirect(route('admin.locations.index'))
        ->assertSessionHasErrors('delete');
});
