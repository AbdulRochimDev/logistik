<?php

use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    $this->service = app(OutboundService::class);
});

test('driver can dispatch and deliver shipment with idempotent PoD', function (): void {
    $lotSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->with('salesOrder.warehouse', 'item')
        ->firstOrFail();
    $lotLocation = Location::where('code', 'RACK-A1')->firstOrFail();
    $itemLot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();

    $this->service->allocate(
        soItem: $lotSoItem,
        qty: 2,
        location: $lotLocation,
        itemLot: $itemLot,
        idempotencyKey: 'ALLOC-DISPATCH',
        actorUserId: $this->driverUser->id,
        allocatedAt: CarbonImmutable::now()
    );

    $shipmentItem = ShipmentItem::where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    $this->service->completePick(new PickLineData(
        shipmentItemId: $shipmentItem->id,
        quantity: 2,
        idempotencyKey: 'PICK-DISPATCH',
        pickedAt: CarbonImmutable::now(),
        actorUserId: $this->driverUser->id
    ));

    $shipment = Shipment::where('tracking_no', 'TRK-5001')->firstOrFail();

    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/dispatch', [
            'shipment_id' => $shipment->id,
            'dispatched_at' => now()->toISOString(),
        ])
        ->assertCreated();

    $shipment->refresh();
    expect($shipment->status)->toBe('dispatched');

    Storage::fake('s3');
    config(['filesystems.default' => 's3']);

    $podPayload = [
        'shipment_id' => $shipment->id,
        'signer_name' => 'Receiver QA',
        'signed_at' => now()->toISOString(),
        'notes' => 'All good',
    ];

    $this->actingAs($this->driverUser, 'sanctum')
        ->withHeaders(['X-Idempotency-Key' => 'POD-CASE-1'])
        ->post('/api/driver/pod', array_merge($podPayload, [
            'photo' => UploadedFile::fake()->image('pod.jpg'),
        ]))
        ->assertCreated();

    $shipment->refresh();
    expect($shipment->status)->toBe('delivered');
    expect(Storage::disk('s3')->allFiles())->not()->toBeEmpty();

    // Replay identical PoD (same payload) should be idempotent
    $this->actingAs($this->driverUser, 'sanctum')
        ->withHeaders(['X-Idempotency-Key' => 'POD-CASE-1'])
        ->post('/api/driver/pod', array_merge($podPayload, [
            'photo' => UploadedFile::fake()->image('pod-repeat.jpg'),
        ]))
        ->assertOk();

    $stock = Stock::query()
        ->where('warehouse_id', $lotSoItem->salesOrder->warehouse_id)
        ->where('location_id', $lotLocation->id)
        ->where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    expect((float) $stock->qty_allocated)->toBe(0.0);

    // another driver cannot deliver same shipment
    $otherDriverUser = User::factory()->create(['email' => 'other-driver@example.com']);
    $otherDriverUser->roles()->syncWithoutDetaching($this->driverUser->roles()->pluck('id'));
    \App\Domain\Outbound\Models\Driver::create([
        'user_id' => $otherDriverUser->id,
        'name' => $otherDriverUser->name,
        'status' => 'active',
    ]);

    $this->actingAs($otherDriverUser, 'sanctum')
        ->post('/api/driver/pod', array_merge($podPayload, ['signed_at' => now()->toISOString(), 'photo' => UploadedFile::fake()->image('pod2.jpg')]))
        ->assertStatus(403);
});
