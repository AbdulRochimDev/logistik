<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\Warehouse;
use Database\Seeders\TiDBInitialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('baseline_loaded', function () {
    $this->seed(TiDBInitialSeeder::class);

    $warehouse = Warehouse::where('code', 'WH1')->first();
    expect($warehouse)->not()->toBeNull();

    $locations = Location::where('warehouse_id', $warehouse->id)->pluck('code');
    expect($locations)->toContain('STAGING', 'RACK-A1', 'RACK-A2', 'OUTBOUND');

    $lotItem = Item::where('sku', 'SKU-LOT-01')->first();
    $stdItem = Item::where('sku', 'SKU-STD-02')->first();

    expect($lotItem)->not()->toBeNull();
    expect($stdItem)->not()->toBeNull();

    $lotStock = Stock::where('warehouse_id', $warehouse->id)
        ->where('location_id', Location::where('code', 'RACK-A1')->where('warehouse_id', $warehouse->id)->value('id'))
        ->where('item_id', $lotItem->id)
        ->first();

    $stdStock = Stock::where('warehouse_id', $warehouse->id)
        ->where('location_id', Location::where('code', 'RACK-A2')->where('warehouse_id', $warehouse->id)->value('id'))
        ->where('item_id', $stdItem->id)
        ->first();

    expect($lotStock?->qty_on_hand)->toBe(30.0);
    expect($stdStock?->qty_on_hand)->toBe(50.0);
    expect($stdStock?->qty_allocated)->toBe(10.0);
});
