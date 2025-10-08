<?php

use App\Domain\Auth\Models\Role;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);

    $this->shipment = Shipment::with('items')->firstOrFail();
    $this->shipmentItem = $this->shipment->items->first();
    $driverRole = Role::where('name', 'driver')->firstOrFail();

    $this->unauthorizedUser = User::factory()->create();
    $this->unauthorizedUser->roles()->sync([$driverRole->id]);

    Driver::factory()->create([
        'user_id' => $this->unauthorizedUser->id,
        'status' => 'active',
    ]);
});

it('blocks driver actions on shipments not assigned to them', function (): void {
    $this->actingAs($this->unauthorizedUser, 'sanctum')
        ->postJson('/api/driver/pick', [
            'shipment_item_id' => $this->shipmentItem->id,
            'qty' => 1,
            'picked_at' => now()->toISOString(),
        ])
        ->assertForbidden();

    $this->actingAs($this->unauthorizedUser, 'sanctum')
        ->postJson('/api/driver/dispatch', [
            'shipment_id' => $this->shipment->id,
            'dispatched_at' => now()->toISOString(),
        ])
        ->assertForbidden();

    $this->actingAs($this->unauthorizedUser, 'sanctum')
        ->postJson('/api/driver/pod', [
            'shipment_id' => $this->shipment->id,
            'signer_name' => 'Unauthorized',
            'signed_at' => now()->toISOString(),
        ])
        ->assertForbidden();
});
