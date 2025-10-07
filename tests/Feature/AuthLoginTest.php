<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AdminUserSeeder::class);
});

test('admin can login with seeded credentials', function () {
    $password = config('wms.auth.admin_password', 'ChangeMe!123');

    $response = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => $password,
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->first());
});

test('login fails with incorrect password', function () {
    $response = $this->from('/login')->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'incorrect',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
