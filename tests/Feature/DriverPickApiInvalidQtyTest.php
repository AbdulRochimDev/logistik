<?php

use App\Domain\Outbound\Models\ShipmentItem;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    $this->shipmentItem = ShipmentItem::with('shipment')->firstOrFail();
});

it('rejects pick quantities that are non-positive or exceed the remaining planned amount', function (): void {
    $payload = [
        'shipment_item_id' => $this->shipmentItem->id,
        'qty' => -1,
        'picked_at' => now()->toISOString(),
    ];

    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/pick', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qty']);

    $this->shipmentItem->refresh();

    $payload['qty'] = (float) $this->shipmentItem->qty_planned + 5;

    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/pick', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qty']);
});
