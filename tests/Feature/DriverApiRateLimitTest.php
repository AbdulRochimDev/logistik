<?php

use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    RateLimiter::clear('driver-api|' . $this->driverUser->id);
});

it('applies rate limiting after 30 driver API calls', function (): void {
    for ($i = 0; $i < 30; $i++) {
        $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/driver/assignments')
            ->assertOk();
    }

    $response = $this->actingAs($this->driverUser, 'sanctum')
        ->getJson('/api/driver/assignments');

    $response->assertStatus(429);
    $response->assertHeader('Retry-After', '60');
    $response->assertJson([
        'message' => 'Terlalu banyak permintaan driver API, coba lagi nanti.',
    ]);
});
