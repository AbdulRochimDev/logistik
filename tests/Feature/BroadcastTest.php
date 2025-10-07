<?php

use App\Domain\Inventory\Events\StockUpdated;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Services\StockService;
use Database\Seeders\TiDBInitialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('stock_updated_event_fires', function () {
    $this->seed(TiDBInitialSeeder::class);

    $service = app(StockService::class);
    $item = Item::where('sku', 'SKU-STD-02')->firstOrFail();
    $location = Location::where('code', 'STAGING')->firstOrFail();

    Event::fake([StockUpdated::class]);

    $service->move(
        type: 'inbound_putaway',
        warehouseId: $location->warehouse_id,
        itemId: $item->id,
        lotId: null,
        fromLocationId: null,
        toLocationId: $location->id,
        qty: 3,
        uom: 'PCS',
        refType: 'TEST',
        refId: 'EVENT-TEST-1',
        actorUserId: null,
        movedAt: now(),
        remarks: 'Broadcast test'
    );

    Event::assertDispatched(StockUpdated::class, function (StockUpdated $event) {
        return $event->payload['sku'] === 'SKU-STD-02'
            && $event->payload['location_code'] === 'STAGING'
            && $event->context === 'scan';
    });
});
