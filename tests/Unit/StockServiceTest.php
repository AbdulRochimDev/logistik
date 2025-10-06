<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inbound putaway increases on hand balance', function () {
    $service = app(StockService::class);

    $location = Location::factory()->create();
    $item = Item::factory()->create();

    $movement = $service->move(
        'inbound_putaway',
        $location->warehouse_id,
        $item->id,
        null,
        null,
        $location->id,
        10,
        'PCS',
        'GRN',
        'GRN-1001',
        1,
        now(),
        'Initial receipt'
    );

    expect($movement->type)->toBe('inbound_putaway');

    $stock = Stock::where([
        'warehouse_id' => $location->warehouse_id,
        'location_id' => $location->id,
        'item_id' => $item->id,
        'item_lot_id' => null,
    ])->first();

    expect($stock)->not->toBeNull()
        ->and((float) $stock->qty_on_hand)->toBe(10.0)
        ->and((float) $stock->qty_allocated)->toBe(0.0);
});

test('picking reduces on hand and allocated balance', function () {
    $service = app(StockService::class);

    $location = Location::factory()->create();
    $item = Item::factory()->create();

    $service->move('inbound_putaway', $location->warehouse_id, $item->id, null, null, $location->id, 12, 'PCS', 'GRN', 'GRN-2001', 1, now());
    $service->move('allocate', $location->warehouse_id, $item->id, null, $location->id, null, 5, 'PCS', 'SO', 'SO-3001', 1, now());

    $service->move('pick', $location->warehouse_id, $item->id, null, $location->id, null, 3, 'PCS', 'PICK', 'PICK-3001', 2, now());

    $stock = Stock::where([
        'warehouse_id' => $location->warehouse_id,
        'location_id' => $location->id,
        'item_id' => $item->id,
        'item_lot_id' => null,
    ])->first();

    expect($stock)->not->toBeNull()
        ->and((float) $stock->qty_on_hand)->toBe(9.0)
        ->and((float) $stock->qty_allocated)->toBe(2.0);
});
