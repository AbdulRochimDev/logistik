<?php

use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
});

test('assigned driver sees allocated or dispatched shipments only', function (): void {
    $response = $this->actingAs($this->driverUser, 'sanctum')
        ->getJson('/api/driver/assignments')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray()->and(count($data))->toBeGreaterThan(0);

    foreach ($data as $shipment) {
        expect(in_array($shipment['status'], ['allocated', 'dispatched']))->toBeTrue();
    }

    $otherUser = User::factory()->create();
    $otherUser->roles()->syncWithoutDetaching([$this->driverUser->roles()->first()->id]);

    $this->actingAs($otherUser, 'sanctum')
        ->getJson('/api/driver/assignments')
        ->assertStatus(403);
});
