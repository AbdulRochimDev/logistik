<?php

use App\Domain\Inbound\Models\GrnHeader;
use App\Domain\Inbound\Models\GrnLine;
use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inbound\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('posts GRN, updates stock, and logs movement', function () {
    $admin = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $location = Location::factory()->create();
    $item = Item::factory()->create();

    $po = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $location->warehouse_id,
        'status' => 'approved',
    ]);

    $poItem = PoItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'ordered_qty' => 10,
    ]);

    $inbound = InboundShipment::factory()->create([
        'purchase_order_id' => $po->id,
        'status' => 'arrived',
    ]);

    $grn = GrnHeader::factory()->create([
        'inbound_shipment_id' => $inbound->id,
        'status' => 'draft',
    ]);

    $line = GrnLine::factory()->create([
        'grn_header_id' => $grn->id,
        'po_item_id' => $poItem->id,
        'putaway_location_id' => $location->id,
        'received_qty' => 7,
    ]);

    $payload = [
        'received_at' => now()->toDateTimeString(),
        'lines' => [
            [
                'id' => $line->id,
                'putaway_location_id' => $location->id,
                'qty' => 7,
            ],
        ],
    ];

    $this->actingAs($admin)
        ->postJson("/admin/inbound/grn/{$grn->id}/post", $payload)
        ->assertSuccessful();

    $stock = Stock::where([
        'warehouse_id' => $location->warehouse_id,
        'location_id' => $location->id,
        'item_id' => $item->id,
    ])->first();

    expect($stock)->not->toBeNull();
    expect((float) $stock->qty_on_hand)->toBeGreaterThanOrEqual(7.0);

    expect(StockMovement::where([
        'ref_type' => 'GRN',
        'ref_id' => $grn->grn_no,
    ])->count())->toBeGreaterThan(0);
});
