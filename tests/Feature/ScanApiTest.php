<?php

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

test('scan api updates stock for inbound and outbound directions', function (): void {
    seed(LogisticsDemoSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $stdItem = Item::where('sku', 'SKU-STD-02')->firstOrFail();
    $staging = Location::where('code', 'STAGING')->firstOrFail();
    $rackA2 = Location::where('code', 'RACK-A2')->firstOrFail();

    $ts = now()->toISOString();

    Sanctum::actingAs($admin, abilities: ['*']);

    $this->postJson('/api/scan', [
        'sku' => $stdItem->sku,
        'qty' => 7,
        'direction' => 'in',
        'location' => $staging->code,
        'ts' => $ts,
        'device_id' => 'HANDHELD-1',
    ])->assertCreated();

    $this->postJson('/api/scan', [
        'sku' => $stdItem->sku,
        'qty' => 5,
        'direction' => 'out',
        'location' => $rackA2->code,
        'ts' => $ts,
        'device_id' => 'HANDHELD-1',
    ])->assertCreated();

    $stagingStock = Stock::where('location_id', $staging->id)
        ->where('item_id', $stdItem->id)
        ->firstOrFail();

    $rackStock = Stock::where('location_id', $rackA2->id)
        ->where('item_id', $stdItem->id)
        ->firstOrFail();

    expect((float) $stagingStock->qty_on_hand)->toBe(7.0);
    expect((float) $rackStock->qty_on_hand)->toBe(45.0);
    expect((float) $rackStock->qty_allocated)->toBe(5.0);
});

test('scan api is idempotent for identical payloads without header', function (): void {
    seed(LogisticsDemoSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $stdItem = Item::where('sku', 'SKU-STD-02')->firstOrFail();
    $rackA2 = Location::where('code', 'RACK-A2')->firstOrFail();

    $payload = [
        'sku' => $stdItem->sku,
        'qty' => 4,
        'direction' => 'out',
        'location' => $rackA2->code,
        'ts' => now()->toISOString(),
        'device_id' => 'HANDHELD-2',
    ];

    Sanctum::actingAs($admin, abilities: ['*']);

    $this->postJson('/api/scan', $payload)
        ->assertCreated();

    $this->postJson('/api/scan', $payload)
        ->assertOk();

    $rackStock = Stock::where('location_id', $rackA2->id)
        ->where('item_id', $stdItem->id)
        ->firstOrFail();

    expect((float) $rackStock->qty_on_hand)->toBe(46.0);
    expect((float) $rackStock->qty_allocated)->toBe(6.0);
});

test('scan api requires lot for lot tracked items', function (): void {
    seed(LogisticsDemoSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $lotItem = Item::where('sku', 'SKU-LOT-01')->firstOrFail();
    $rackA1 = Location::where('code', 'RACK-A1')->firstOrFail();

    $payload = [
        'sku' => $lotItem->sku,
        'qty' => 2,
        'direction' => 'out',
        'location' => $rackA1->code,
        'ts' => now()->toISOString(),
        'device_id' => 'HANDHELD-3',
    ];

    Sanctum::actingAs($admin, abilities: ['*']);

    $this->postJson('/api/scan', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lot_no']);
});
