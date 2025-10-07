<?php

use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
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

test('posting grn via quick action updates stock and is idempotent', function () {
    $context = seedContext();
    $inbound = InboundShipment::factory()->create([
        'purchase_order_id' => $context['purchaseOrder']->id,
        'status' => 'arrived',
    ]);

    $payload = [
        'inbound_shipment_id' => $inbound->id,
        'received_at' => now()->toISOString(),
        'notes' => 'Inbound dock 1',
        'lines' => [
            [
                'po_item_id' => $context['lotPoItem']->id,
                'item_id' => $context['lotItem']->id,
                'qty' => 5,
                'to_location_id' => $context['rackA1']->id,
                'lot_no' => 'LOT-QA-01',
            ],
            [
                'po_item_id' => $context['stdPoItem']->id,
                'item_id' => $context['stdItem']->id,
                'qty' => 3,
                'to_location_id' => $context['rackA2']->id,
            ],
        ],
    ];

    $initialLotQty = stockQty($context['lotItem']->id, $context['rackA1']->id);
    $initialStdQty = stockQty($context['stdItem']->id, $context['rackA2']->id);

    $this->actingAs($this->admin)
        ->post(route('admin.grn.store'), $payload)
        ->assertRedirect(route('admin.dashboard'));

    expect(stockQty($context['lotItem']->id, $context['rackA1']->id))->toBe($initialLotQty + 5.0);
    expect(stockQty($context['stdItem']->id, $context['rackA2']->id))->toBe($initialStdQty + 3.0);

    $this->actingAs($this->admin)
        ->post(route('admin.grn.store'), $payload)
        ->assertRedirect(route('admin.dashboard'));

    expect(StockMovement::where('ref_type', 'GRN')->count())->toBe(2);
});

function seedContext(): array
{
    $purchaseOrder = PurchaseOrder::where('po_no', 'PO-1001')->firstOrFail();
    $lotItem = Item::where('sku', 'SKU-LOT-01')->firstOrFail();
    $stdItem = Item::where('sku', 'SKU-STD-02')->firstOrFail();
    $lotPoItem = PoItem::where('purchase_order_id', $purchaseOrder->id)->where('item_id', $lotItem->id)->firstOrFail();
    $stdPoItem = PoItem::where('purchase_order_id', $purchaseOrder->id)->where('item_id', $stdItem->id)->firstOrFail();
    $rackA1 = Location::where('code', 'RACK-A1')->firstOrFail();
    $rackA2 = Location::where('code', 'RACK-A2')->firstOrFail();

    return compact('purchaseOrder', 'lotItem', 'stdItem', 'lotPoItem', 'stdPoItem', 'rackA1', 'rackA2');
}

function stockQty(int $itemId, int $locationId): float
{
    return (float) Stock::query()
        ->where('item_id', $itemId)
        ->where('location_id', $locationId)
        ->sum('qty_on_hand');
}
