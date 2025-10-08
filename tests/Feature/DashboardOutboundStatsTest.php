<?php

use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders outbound KPIs, warehouse aggregates, and activity feed correctly', function (): void {
    $this->seed(LogisticsDemoSeeder::class);

    $service = app(OutboundService::class);
    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    /** @var ShipmentItem $shipmentItem */
    $shipmentItem = ShipmentItem::query()
        ->with('shipment')
        ->whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->firstOrFail();

    $shipment = $shipmentItem->shipment;

    $pickAt = CarbonImmutable::now()->startOfDay()->addHours(9);

    $service->completePick(new PickLineData(
        shipmentItemId: $shipmentItem->id,
        quantity: 2.0,
        idempotencyKey: 'DASH-PICK-1',
        pickedAt: $pickAt,
        actorUserId: $admin->id,
    ));

    $deliverAt = CarbonImmutable::now()->startOfDay()->addHours(11);

    $service->deliver(new ShipmentPodData(
        shipmentId: $shipment->id,
        signerName: 'Dashboard QA',
        signedAt: $deliverAt,
        idempotencyKey: 'DASH-POD-1',
        actorUserId: $admin->id,
    ));

    actingAs($admin);

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk();

    $response->assertViewHas('pickedToday', function ($value): bool {
        return (float) $value === 2.0;
    });

    $response->assertViewHas('deliveredToday', function ($value): bool {
        return (float) $value >= 2.0;
    });

    $response->assertViewHas('openShipments', 0);

    $response->assertViewHas('warehouseBreakdown', function ($collection): bool {
        $first = $collection->first();

        return $first !== null
            && (float) ($first->picked_today ?? 0) >= 2.0
            && (float) ($first->delivered_today ?? 0) >= 2.0;
    });

    $response->assertViewHas('activityFeed', function ($feed) use ($shipment): bool {
        $feedCollection = collect($feed);
        $first = $feedCollection->first();

        expect($first['title'])->toContain('Shipment');

        $hasPickEntry = $feedCollection->contains(function (array $entry): bool {
            return str_contains($entry['title'], 'Pick Completed');
        });

        $hasDeliveredEntry = $feedCollection->contains(function (array $entry) use ($shipment): bool {
            return str_contains($entry['title'], (string) ($shipment->shipment_no ?? $shipment->id))
                && $entry['highlight'] === 'Delivered';
        });

        return $hasPickEntry && $hasDeliveredEntry;
    });
});
