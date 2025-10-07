<?php

use App\Domain\Inventory\Models\Item;
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

test('admin can create unique sku item', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.items.store'), [
            'sku' => 'SKU-NEW-001',
            'name' => 'New Item',
            'default_uom' => 'PCS',
            'is_lot_tracked' => false,
        ])
        ->assertRedirect(route('admin.items.index'));

    expect(Item::where('sku', 'SKU-NEW-001')->exists())->toBeTrue();
});

test('duplicate sku rejected', function () {
    Item::factory()->create(['sku' => 'SKU-DUP', 'default_uom' => 'PCS']);

    $response = $this->actingAs($this->admin)
        ->from(route('admin.items.create'))
        ->post(route('admin.items.store'), [
            'sku' => 'SKU-DUP',
            'name' => 'Dup Item',
            'default_uom' => 'PCS',
            'is_lot_tracked' => false,
        ]);

    $response->assertRedirect(route('admin.items.create'));
    $response->assertSessionHasErrors('sku');
});
