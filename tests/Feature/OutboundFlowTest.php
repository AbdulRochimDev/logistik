<?php

use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Outbound\Models\PickLine;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\SoItem;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

test('outbound flow allocates, picks, dispatches, and delivers idempotently', function (): void {
    seed(LogisticsDemoSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $driver = User::where('email', 'driver@example.com')->firstOrFail();

    $lotSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))->firstOrFail();
    $stdSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-STD-02'))->firstOrFail();
    $rackA1 = Location::where('code', 'RACK-A1')->firstOrFail();
    $rackA2 = Location::where('code', 'RACK-A2')->firstOrFail();
    $lot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();

    $lotPayload = [
        'so_item_id' => $lotSoItem->id,
        'location_id' => $rackA1->id,
        'qty' => 6,
        'lot_no' => 'LOT-01',
    ];

    $stdPayload = [
        'so_item_id' => $stdSoItem->id,
        'location_id' => $rackA2->id,
        'qty' => 8,
    ];

    Sanctum::actingAs($admin, abilities: ['*']);

    $this->postJson('/api/admin/outbound/allocate', $lotPayload)->assertCreated();
    $this->postJson('/api/admin/outbound/allocate', $stdPayload)->assertCreated();

    $lotPickLine = PickLine::where('so_item_id', $lotSoItem->id)->firstOrFail();
    $stdPickLine = PickLine::where('so_item_id', $stdSoItem->id)->firstOrFail();

    $this->postJson('/api/admin/outbound/pick/complete', [
        'pick_line_id' => $lotPickLine->id,
        'qty' => 6,
        'picked_at' => now()->toISOString(),
    ])->assertCreated();

    $this->postJson('/api/admin/outbound/pick/complete', [
        'pick_line_id' => $stdPickLine->id,
        'qty' => 8,
        'picked_at' => now()->toISOString(),
    ])->assertCreated();

    $lotStockAfterPick = Stock::where('location_id', $rackA1->id)
        ->where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $lot->id)
        ->firstOrFail();

    $stdStockAfterPick = Stock::where('location_id', $rackA2->id)
        ->where('item_id', $stdSoItem->item_id)
        ->firstOrFail();

    expect((float) $lotStockAfterPick->qty_allocated)->toBe(0.0);
    expect((float) $stdStockAfterPick->qty_allocated)->toBe(10.0);

    $shipment = Shipment::where('tracking_no', 'TRK-5001')->firstOrFail();

    $this->postJson('/api/admin/outbound/shipment/dispatch', [
        'shipment_id' => $shipment->id,
        'dispatched_at' => now()->toISOString(),
        'carrier' => 'Internal Fleet',
    ])->assertOk()
        ->assertJsonPath('data.outbound_shipment.status', 'dispatched');

    $deliverPayload = [
        'shipment_id' => $shipment->id,
        'signed_by' => 'Recipient QA',
        'signed_at' => now()->toISOString(),
        'notes' => 'Delivered intact',
    ];

    Sanctum::actingAs($driver, abilities: ['*']);

    $firstDeliver = $this->withHeader('X-Idempotency-Key', 'POD-FLOW-KEY')
        ->postJson('/api/admin/outbound/shipment/deliver', $deliverPayload)
        ->assertOk()
        ->assertJsonPath('data.idempotency_key', 'POD-FLOW-KEY')
        ->json('data.movements');

    expect($firstDeliver)->not()->toBeEmpty();

    $secondDeliver = $this->withHeader('X-Idempotency-Key', 'POD-FLOW-KEY')
        ->postJson('/api/admin/outbound/shipment/deliver', $deliverPayload)
        ->assertOk()
        ->json('data.movements');

    expect($secondDeliver)->toHaveCount(count($firstDeliver));

    $lotStock = Stock::where('location_id', $rackA1->id)
        ->where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $lot->id)
        ->firstOrFail();
    $stdStock = Stock::where('location_id', $rackA2->id)
        ->where('item_id', $stdSoItem->item_id)
        ->firstOrFail();

    expect((float) $lotStock->qty_on_hand)->toBe(24.0);
    expect((float) $lotStock->qty_allocated)->toBe(0.0);
    expect((float) $stdStock->qty_on_hand)->toBe(42.0);
    expect((float) $stdStock->qty_allocated)->toBe(10.0);
});
