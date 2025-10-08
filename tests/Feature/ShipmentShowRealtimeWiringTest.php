<?php

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders realtime shipment channel wiring on shipment detail page', function (): void {
    $this->seed(LogisticsDemoSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $shipment = Shipment::query()->firstOrFail();

    actingAs($admin);

    $response = $this->get(route('admin.shipments.show', $shipment));

    $response->assertOk();
    $response->assertSee('wms.outbound.shipment.' . $shipment->id, false);
    $response->assertSee('data-shipment-progress', false);
    $response->assertSee((string) $shipment->id, false);
});
