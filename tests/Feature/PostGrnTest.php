<?php

use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function seedWarehouseContext(): array
{
    seed(AdminUserSeeder::class);
    seed(LogisticsDemoSeeder::class);

    $admin = User::firstOrFail();
    $purchaseOrder = PurchaseOrder::where('po_no', 'PO-1001')->firstOrFail();
    $lotItem = Item::where('sku', 'SKU-LOT-01')->firstOrFail();
    $stdItem = Item::where('sku', 'SKU-STD-02')->firstOrFail();
    $lotPoItem = PoItem::where('purchase_order_id', $purchaseOrder->id)->where('item_id', $lotItem->id)->firstOrFail();
    $stdPoItem = PoItem::where('purchase_order_id', $purchaseOrder->id)->where('item_id', $stdItem->id)->firstOrFail();
    $rackA1 = Location::where('code', 'RACK-A1')->firstOrFail();
    $rackA2 = Location::where('code', 'RACK-A2')->firstOrFail();

    return compact('admin', 'purchaseOrder', 'lotItem', 'stdItem', 'lotPoItem', 'stdPoItem', 'rackA1', 'rackA2');
}

it('ensures GRN idempotency without header by deriving deterministic key', function () {
    $context = seedWarehouseContext();

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $context['lotPoItem']->id,
                'item_id' => $context['lotItem']->id,
                'qty' => 10,
                'to_location_id' => $context['rackA1']->id,
                'lot_no' => 'LOT-API-01',
            ],
            [
                'po_item_id' => $context['stdPoItem']->id,
                'item_id' => $context['stdItem']->id,
                'qty' => 5,
                'to_location_id' => $context['rackA2']->id,
            ],
        ],
    ];

    $initialLotQty = (float) Stock::where('location_id', $context['rackA1']->id)
        ->where('item_id', $context['lotItem']->id)
        ->sum('qty_on_hand');

    $initialStdQty = (float) Stock::where('location_id', $context['rackA2']->id)
        ->where('item_id', $context['stdItem']->id)
        ->sum('qty_on_hand');

    $firstResponse = $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertCreated()
        ->json('data');

    expect($firstResponse['lines_processed'])->toBe(2);
    expect($firstResponse['lines_skipped'])->toBe(0);
    expect($firstResponse['external_idempotency_key'])->not()->toBeNull();

    $secondResponse = $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertOk()
        ->json('data');

    expect($secondResponse['lines_processed'])->toBe(0);
    expect($secondResponse['lines_skipped'])->toBe(2);
    expect($secondResponse['external_idempotency_key'])->toBe($firstResponse['external_idempotency_key']);

    $postLotQty = (float) Stock::where('location_id', $context['rackA1']->id)
        ->where('item_id', $context['lotItem']->id)
        ->sum('qty_on_hand');

    $postStdQty = (float) Stock::where('location_id', $context['rackA2']->id)
        ->where('item_id', $context['stdItem']->id)
        ->sum('qty_on_hand');

    expect($postLotQty)->toBe($initialLotQty + 10.0);
    expect($postStdQty)->toBe($initialStdQty + 5.0);
    expect(StockMovement::where('ref_type', 'GRN')->count())->toBe(2);
});

it('supports explicit X-Idempotency-Key header for GRN replay', function () {
    $context = seedWarehouseContext();

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $context['lotPoItem']->id,
                'item_id' => $context['lotItem']->id,
                'qty' => 4,
                'to_location_id' => $context['rackA1']->id,
                'lot_no' => 'LOT-API-HEADER',
            ],
        ],
    ];

    $headerKey = 'KEY-12345';

    $first = $this->actingAs($context['admin'])
        ->withHeader('X-Idempotency-Key', $headerKey)
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertCreated()
        ->json('data');

    expect($first['external_idempotency_key'])->toBe($headerKey);
    expect($first['lines_processed'])->toBe(1);

    $second = $this->actingAs($context['admin'])
        ->withHeader('X-Idempotency-Key', $headerKey)
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertOk()
        ->json('data');

    expect($second['lines_processed'])->toBe(0);
    expect($second['lines_skipped'])->toBe(1);
});

it('requires lot number for lot-tracked items', function () {
    $context = seedWarehouseContext();

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $context['lotPoItem']->id,
                'item_id' => $context['lotItem']->id,
                'qty' => 3,
                'to_location_id' => $context['rackA1']->id,
            ],
        ],
    ];

    $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.lot_no']);
});

it('rejects GRN when location belongs to a different warehouse', function () {
    $context = seedWarehouseContext();

    $otherWarehouse = Warehouse::factory()->create(['code' => 'WH2']);
    $foreignLocation = Location::factory()->create([
        'warehouse_id' => $otherWarehouse->id,
        'code' => 'FOREIGN-A1',
    ]);

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $context['stdPoItem']->id,
                'item_id' => $context['stdItem']->id,
                'qty' => 2,
                'to_location_id' => $foreignLocation->id,
            ],
        ],
    ];

    $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.to_location_id']);
});

it('rejects GRN quantities that exceed remaining PO amount', function () {
    $context = seedWarehouseContext();

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $context['lotPoItem']->id,
                'item_id' => $context['lotItem']->id,
                'qty' => 25,
                'to_location_id' => $context['rackA1']->id,
                'lot_no' => 'LOT-EXCEED',
            ],
        ],
    ];

    $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertUnprocessable()
        ->assertJsonFragment([
            'message' => 'Received quantity exceeds outstanding purchase order quantity.',
        ]);
});

it('rejects GRN lines when PO item and item mismatch', function () {
    $context = seedWarehouseContext();

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $context['lotPoItem']->id,
                'item_id' => $context['stdItem']->id,
                'qty' => 1,
                'to_location_id' => $context['rackA1']->id,
                'lot_no' => 'LOT-MISMATCH',
            ],
        ],
    ];

    $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'PO item mismatch with provided item.',
        ]);
});

it('treats identical payloads with different line ordering as the same idempotent request', function () {
    $context = seedWarehouseContext();

    $inboundShipment = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $lines = [
        [
            'po_item_id' => $context['lotPoItem']->id,
            'item_id' => $context['lotItem']->id,
            'qty' => 6,
            'to_location_id' => $context['rackA1']->id,
            'lot_no' => 'LOT-ORDER-A',
        ],
        [
            'po_item_id' => $context['stdPoItem']->id,
            'item_id' => $context['stdItem']->id,
            'qty' => 4,
            'to_location_id' => $context['rackA2']->id,
        ],
    ];

    $payload = [
        'inbound_shipment_id' => $inboundShipment->id,
        'received_at' => now()->toISOString(),
        'lines' => $lines,
    ];

    $first = $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertCreated()
        ->json('data');

    $secondPayload = $payload;
    $secondPayload['lines'] = array_reverse($lines);

    $second = $this->actingAs($context['admin'])
        ->postJson('/api/admin/inbound/grn', $secondPayload)
        ->assertOk()
        ->json('data');

    expect($second['external_idempotency_key'])->toBe($first['external_idempotency_key']);
    expect($second['lines_processed'])->toBe(0);
    expect($second['lines_skipped'])->toBe(2);
});

it('blocks GRN posting for non-admin users', function () {
    seed(LogisticsDemoSeeder::class);

    $driver = User::where('email', 'driver@example.com')->firstOrFail();
    $po = PurchaseOrder::firstOrFail();
    $lotItem = Item::where('sku', 'SKU-LOT-01')->firstOrFail();
    $poItem = PoItem::where('item_id', $lotItem->id)->firstOrFail();
    $location = Location::where('code', 'RACK-A1')->firstOrFail();

    $inbound = InboundShipment::factory()->create([
        'purchase_order_id' => $po->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inbound->id,
        'received_at' => now()->toISOString(),
        'lines' => [
            [
                'po_item_id' => $poItem->id,
                'item_id' => $lotItem->id,
                'qty' => 1,
                'to_location_id' => $location->id,
                'lot_no' => 'LOT-BLOCK',
            ],
        ],
    ];

    $this->actingAs($driver)
        ->postJson('/api/admin/inbound/grn', $payload)
        ->assertForbidden();
});
