<?php

use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    $this->service = app(OutboundService::class);
});

test('driver can pick shipment item and replay is idempotent', function (): void {
    $lotSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->with('salesOrder.warehouse', 'item')
        ->firstOrFail();
    $lotLocation = Location::where('code', 'RACK-A1')->firstOrFail();
    $itemLot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();

    $this->service->allocate(
        soItem: $lotSoItem,
        qty: 3,
        location: $lotLocation,
        itemLot: $itemLot,
        idempotencyKey: 'DRIVER-PICK',
        actorUserId: $this->driverUser->id,
        allocatedAt: CarbonImmutable::now()
    );

    $shipmentItem = ShipmentItem::where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    $payload = [
        'shipment_item_id' => $shipmentItem->id,
        'qty' => 3,
        'picked_at' => now()->toISOString(),
        'remarks' => 'First pick',
    ];

    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/pick', $payload)
        ->assertCreated();

    $stock = Stock::query()
        ->where('warehouse_id', $lotSoItem->salesOrder->warehouse_id)
        ->where('location_id', $lotLocation->id)
        ->where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(27.0);
    expect((float) $stock->qty_allocated)->toBe(0.0);

    // replay with same data (fallback idempotency) should be 200 and unchanged
    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/pick', $payload)
        ->assertOk();

    $shipmentItem->refresh();
    expect((float) $shipmentItem->qty_picked)->toBe(3.0);

    // invalid quantity > planned should fail
    $payload['qty'] = 10;
    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/pick', $payload)
        ->assertStatus(422);
});
