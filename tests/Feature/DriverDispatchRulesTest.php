<?php

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    $this->shipment = Shipment::with(['driver', 'vehicle'])->firstOrFail();
});

it('requires an active driver and vehicle before dispatching', function (): void {
    $this->shipment->driver?->update(['status' => 'inactive']);
    $this->shipment->vehicle?->update(['status' => 'inactive']);

    $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/dispatch', [
            'shipment_id' => $this->shipment->id,
            'dispatched_at' => now()->toISOString(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['shipment_id']);
});

it('returns 409 when dispatch is attempted after delivery', function (): void {
    $this->shipment->update([
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    $response = $this->actingAs($this->driverUser, 'sanctum')
        ->postJson('/api/driver/dispatch', [
            'shipment_id' => $this->shipment->id,
            'dispatched_at' => now()->toISOString(),
        ]);

    $response->assertStatus(409);
    $response->assertHeader('Idempotency-Key');
    $response->assertJson(['message' => 'Shipment sudah delivered.']);
});
