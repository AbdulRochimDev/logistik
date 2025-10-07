<?php

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;
use App\Domain\Outbound\Services\OutboundService;
use Carbon\CarbonImmutable;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->service = app(OutboundService::class);
});

it('allocates stock and rejects when outstanding or availability insufficient', function (): void {
    $lotSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->with('salesOrder.warehouse', 'item')
        ->firstOrFail();
    $lotLocation = Location::where('code', 'RACK-A1')->firstOrFail();
    $itemLot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();

    $result = $this->service->allocate(
        soItem: $lotSoItem,
        qty: 5,
        location: $lotLocation,
        itemLot: $itemLot,
        idempotencyKey: Str::uuid()->toString(),
        actorUserId: 1,
        allocatedAt: CarbonImmutable::now(),
        remarks: 'initial allocation'
    );

    expect((float) $result['so_item']->allocated_qty)->toBe(5.0);

    // exhaustion of outstanding quantity
    $this->expectException(StockException::class);
    $this->expectExceptionMessage('Allocation exceeds outstanding order quantity.');

    $this->service->allocate(
        soItem: $lotSoItem->fresh(),
        qty: 20,
        location: $lotLocation,
        itemLot: $itemLot,
        idempotencyKey: 'EXCEED',
        actorUserId: 1,
        allocatedAt: CarbonImmutable::now()
    );
});

it('completes pick updating stock levels and shipment item quantities', function (): void {
    $lotSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->with('salesOrder.warehouse', 'item')
        ->firstOrFail();
    $lotLocation = Location::where('code', 'RACK-A1')->firstOrFail();
    $itemLot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();

    $this->service->allocate(
        soItem: $lotSoItem,
        qty: 4,
        location: $lotLocation,
        itemLot: $itemLot,
        idempotencyKey: 'ALLOC-PICK',
        actorUserId: 1,
        allocatedAt: CarbonImmutable::now()
    );

    $shipmentItem = ShipmentItem::where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    $pickDto = new PickLineData(
        shipmentItemId: $shipmentItem->id,
        quantity: 4,
        idempotencyKey: Str::uuid()->toString(),
        pickedAt: CarbonImmutable::now(),
        actorUserId: 1,
        remarks: 'Driver pick'
    );

    $result = $this->service->completePick($pickDto);

    expect($result['movement']->type)->toBe('pick');
    expect((float) $result['shipment_item']->qty_picked)->toBe(4.0);

    $stock = Stock::query()
        ->where('warehouse_id', $lotSoItem->salesOrder->warehouse_id)
        ->where('location_id', $lotLocation->id)
        ->where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(26.0);
    expect((float) $stock->qty_allocated)->toBe(0.0);

    // replay idempotent
    $replay = $this->service->completePick($pickDto);
    expect($replay['movement']->wasRecentlyCreated)->toBeFalse();
    expect((float) $replay['shipment_item']->qty_picked)->toBe(4.0);
});

it('delivers idempotently and records pod without altering stock twice', function (): void {
    $lotSoItem = SoItem::whereHas('item', fn ($query) => $query->where('sku', 'SKU-LOT-01'))
        ->with('salesOrder.warehouse', 'item')
        ->firstOrFail();
    $lotLocation = Location::where('code', 'RACK-A1')->firstOrFail();
    $itemLot = ItemLot::where('lot_no', 'LOT-01')->firstOrFail();

    $this->service->allocate(
        soItem: $lotSoItem,
        qty: 3,
        location: $lotLocation,
        itemLot: $itemLot,
        idempotencyKey: 'ALLOC-DELIVER',
        actorUserId: 1,
        allocatedAt: CarbonImmutable::now()
    );

    $shipmentItem = ShipmentItem::where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    $pickDto = new PickLineData(
        shipmentItemId: $shipmentItem->id,
        quantity: 3,
        idempotencyKey: 'PICK-DELIVER',
        pickedAt: CarbonImmutable::now(),
        actorUserId: 1
    );
    $this->service->completePick($pickDto);

    $shipment = Shipment::where('tracking_no', 'TRK-5001')->firstOrFail();

    $podDto = new ShipmentPodData(
        shipmentId: $shipment->id,
        signerName: 'Recipient QA',
        signedAt: CarbonImmutable::now(),
        idempotencyKey: 'POD-DELIVER',
        actorUserId: 2,
        signerId: 'REC001',
        notes: 'Received in good condition'
    );

    $first = $this->service->deliver($podDto);
    expect($first['pod']->signed_by)->toBe('Recipient QA');
    expect($first['created'])->toBeTrue();

    $stock = Stock::query()
        ->where('warehouse_id', $lotSoItem->salesOrder->warehouse_id)
        ->where('location_id', $lotLocation->id)
        ->where('item_id', $lotSoItem->item_id)
        ->where('item_lot_id', $itemLot->id)
        ->firstOrFail();

    expect((float) $stock->qty_allocated)->toBe(0.0);

    $second = $this->service->deliver($podDto);
    expect(count($second['movements']))->toBe(count($first['movements']));
    expect($second['created'])->toBeFalse();
    expect((float) $stock->fresh()->qty_allocated)->toBe(0.0);
});
