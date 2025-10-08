<?php

use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Events\PickCompleted;
use App\Domain\Outbound\Events\ShipmentDelivered;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('defers outbound broadcast events until outer transaction commits', function (): void {
    $this->seed(LogisticsDemoSeeder::class);

    $service = app(OutboundService::class);
    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    /** @var ShipmentItem $shipmentItem */
    $shipmentItem = ShipmentItem::query()
        ->with('shipment')
        ->whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->firstOrFail();

    $shipment = $shipmentItem->shipment;

    Event::fake([
        PickCompleted::class,
        ShipmentDelivered::class,
    ]);

    DB::beginTransaction();

    $service->completePick(new PickLineData(
        shipmentItemId: $shipmentItem->id,
        quantity: 2.0,
        idempotencyKey: 'TXN-PICK-1',
        pickedAt: CarbonImmutable::now(),
        actorUserId: $admin->id,
    ));

    $service->deliver(new ShipmentPodData(
        shipmentId: $shipment->id,
        signerName: 'QA Receiver',
        signedAt: CarbonImmutable::now()->addMinutes(5),
        idempotencyKey: 'TXN-POD-1',
        actorUserId: $admin->id,
    ));

    Event::assertDispatchedTimes(PickCompleted::class, 0);
    Event::assertDispatchedTimes(ShipmentDelivered::class, 0);

    DB::commit();

    Event::assertDispatchedTimes(PickCompleted::class, 1);
    Event::assertDispatchedTimes(ShipmentDelivered::class, 1);
});
