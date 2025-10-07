<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AdminUserSeeder::class);
    $this->seed(LogisticsDemoSeeder::class);
    $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
});

test('dashboard shows aggregated stock metrics', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertViewHas('totals', function ($totals) {
        return (int) round($totals->qty_on_hand ?? 0) === 80
            && (int) round($totals->qty_allocated ?? 0) === 10
            && (int) round($totals->qty_available ?? 0) === 70;
    });
});
