<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inbound_putaway_increments_on_hand', function () {
    $this->seed(LogisticsDemoSeeder::class);

    $service = app(StockService::class);

    $warehouse = Warehouse::where('code', 'WH1')->firstOrFail();
    $location = Location::where('code', 'STAGING')->firstOrFail();
    $item = Item::where('sku', 'SKU-STD-02')->firstOrFail();

    $initialQty = (float) (Stock::where([
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        'item_id' => $item->id,
        'item_lot_id' => null,
    ])->value('qty_on_hand') ?? 0);

    $movement = $service->move(
        'inbound_putaway',
        $warehouse->id,
        $item->id,
        null,
        null,
        $location->id,
        10,
        'PCS',
        'GRN',
        'SEED-GRN-1001',
        1,
        now(),
        'Initial receipt'
    );

    $updatedQty = (float) Stock::where([
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        'item_id' => $item->id,
        'item_lot_id' => null,
    ])->value('qty_on_hand');

    expect($movement->type)->toBe('inbound_putaway');
    expect($updatedQty)->toBe($initialQty + 10.0);
});

test('pick_decrements_on_hand_and_allocated', function () {
    $this->seed(LogisticsDemoSeeder::class);

    $service = app(StockService::class);

    $warehouse = Warehouse::where('code', 'WH1')->firstOrFail();
    $location = Location::where('code', 'RACK-A2')->firstOrFail();
    $item = Item::where('sku', 'SKU-STD-02')->firstOrFail();

    $service->move('inbound_putaway', $warehouse->id, $item->id, null, null, $location->id, 12, 'PCS', 'GRN', 'SEED-GRN-2001', 1, now());
    $service->move('allocate', $warehouse->id, $item->id, null, $location->id, null, 5, 'PCS', 'SO', 'SEED-SO-3001', 1, now());
    $service->move('pick', $warehouse->id, $item->id, null, $location->id, null, 3, 'PCS', 'PICK', 'SEED-PICK-3001', 2, now());

    $stock = Stock::where([
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        'item_id' => $item->id,
        'item_lot_id' => null,
    ])->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(59.0);
    expect((float) $stock->qty_allocated)->toBe(2.0);
});

test('idempotency_duplicate_ref_no_effect', function () {
    $this->seed(LogisticsDemoSeeder::class);

    $service = app(StockService::class);

    $warehouse = Warehouse::where('code', 'WH1')->firstOrFail();
    $location = Location::where('code', 'STAGING')->firstOrFail();
    $item = Item::where('sku', 'SKU-STD-02')->firstOrFail();

    $service->move('inbound_putaway', $warehouse->id, $item->id, null, null, $location->id, 5, 'PCS', 'GRN', 'REF-9001', 1, now());
    $service->move('inbound_putaway', $warehouse->id, $item->id, null, null, $location->id, 5, 'PCS', 'GRN', 'REF-9001', 1, now());

    $stock = Stock::where('location_id', $location->id)
        ->where('item_id', $item->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(5.0);
});
