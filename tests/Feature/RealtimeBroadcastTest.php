<?php

use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Events\PickCompleted;
use App\Domain\Outbound\Events\ShipmentDelivered;
use App\Domain\Outbound\Events\ShipmentDispatched;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Services\OutboundService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('broadcasts outbound events with expected payload and channel', function (): void {
    $this->seed(LogisticsDemoSeeder::class);

    $service = app(OutboundService::class);
    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    /** @var ShipmentItem $shipmentItem */
    $shipmentItem = ShipmentItem::query()
        ->with('shipment')
        ->whereHas('item', fn ($query) => $query->where('sku', 'SKU-STD-02'))
        ->firstOrFail();

    $shipment = $shipmentItem->shipment;
    $shipment->load(['driver', 'vehicle']);

    Event::fake([
        PickCompleted::class,
        ShipmentDispatched::class,
        ShipmentDelivered::class,
    ]);

    $pickAt = CarbonImmutable::now();

    $service->completePick(new PickLineData(
        shipmentItemId: $shipmentItem->id,
        quantity: 3.0,
        idempotencyKey: 'TEST-PICK-1',
        pickedAt: $pickAt,
        actorUserId: $admin->id,
    ));

    Event::assertDispatched(PickCompleted::class, function (PickCompleted $event) use ($shipmentItem, $pickAt, $admin): bool {
        $channels = $event->broadcastOn();
        expect($channels)->toBeArray();

        /** @var Channel $channel */
        $channel = $channels[0];

        expect($channel->name)->toBe('private-wms.outbound.shipment.' . $shipmentItem->shipment_id);
        expect($event->broadcastAs())->toBe('pick.completed');

        $payload = $event->broadcastWith();
        expect($payload['shipment_item_id'])->toBe($shipmentItem->id);
        expect($payload['item_id'])->toBe($shipmentItem->item_id);
        expect($payload['qty_picked'])->toBeFloat()->toBe(3.0);
        expect($payload['picked_at'])->toBe($pickAt->toIso8601String());
        expect($payload['actor_user_id'])->toBe($admin->id);

        return true;
    });

    $dispatchAt = CarbonImmutable::now()->addMinute();

    $service->dispatch($shipment, 'DISPATCH-KEY', $dispatchAt, $admin->id);

    Event::assertDispatched(ShipmentDispatched::class, function (ShipmentDispatched $event) use ($shipment, $dispatchAt): bool {
        $channel = $event->broadcastOn()[0];

        expect($channel->name)->toBe('private-wms.outbound.shipment.' . $shipment->id);
        expect($event->broadcastAs())->toBe('shipment.dispatched');

        $payload = $event->broadcastWith();
        expect($payload['shipment_id'])->toBe($shipment->id);
        expect($payload['driver_id'])->toBe($shipment->driver_id);
        expect($payload['vehicle_id'])->toBe($shipment->vehicle_id);
        expect($payload['dispatched_at'])->toBe($dispatchAt->toIso8601String());

        return true;
    });

    $deliverAt = CarbonImmutable::now()->addMinutes(2);

    $service->deliver(new ShipmentPodData(
        shipmentId: $shipment->id,
        signerName: 'Receiver QA',
        signedAt: $deliverAt,
        idempotencyKey: 'POD-TEST-1',
        actorUserId: $admin->id,
        notes: 'Signed without issue',
    ));

    Event::assertDispatched(ShipmentDelivered::class, function (ShipmentDelivered $event) use ($shipment, $deliverAt): bool {
        $channel = $event->broadcastOn()[0];

        expect($channel->name)->toBe('private-wms.outbound.shipment.' . $shipment->id);
        expect($event->broadcastAs())->toBe('shipment.delivered');

        $payload = $event->broadcastWith();
        expect($payload['shipment_id'])->toBe($shipment->id);
        expect($payload['pod_id'])->toBeInt();
        expect($payload['delivered_at'])->toBe($deliverAt->toIso8601String());

        return true;
    });
});
