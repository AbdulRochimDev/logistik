<?php

use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inbound\Models\Supplier;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Warehouse;
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

test('admin can create purchase order with lines', function () {
    $supplier = Supplier::firstOrFail();
    $warehouse = Warehouse::firstOrFail();
    $itemA = Item::where('sku', 'SKU-LOT-01')->firstOrFail();
    $itemB = Item::where('sku', 'SKU-STD-02')->firstOrFail();

    $payload = [
        'po_no' => 'PO-TEST-100',
        'status' => 'approved',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'eta' => now()->addWeek()->format('Y-m-d'),
        'notes' => 'Testing order',
        'lines' => [
            ['item_id' => $itemA->id, 'uom' => 'PCS', 'qty_ordered' => 12],
            ['item_id' => $itemB->id, 'uom' => 'BOX', 'qty_ordered' => 5],
        ],
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.purchase-orders.store'), $payload)
        ->assertRedirect();

    $po = PurchaseOrder::where('po_no', 'PO-TEST-100')->with('items')->firstOrFail();

    expect($po->items)->toHaveCount(2)
        ->and($po->items->pluck('ordered_qty')->all())->toEqualCanonicalizing([12.0, 5.0]);
});

test('purchase order update respects existing lines', function () {
    $po = PurchaseOrder::factory()
        ->has(PoItem::factory()->count(2))
        ->create([
            'po_no' => 'PO-1111',
            'status' => 'draft',
            'created_by' => $this->admin->id,
            'supplier_id' => Supplier::first()->id,
            'warehouse_id' => Warehouse::first()->id,
        ]);

    $po->load('items');

    $item = Item::where('sku', 'SKU-STD-02')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.purchase-orders.update', $po), [
            'po_no' => 'PO-1111',
            'status' => 'approved',
            'supplier_id' => $po->supplier_id,
            'warehouse_id' => $po->warehouse_id,
            'eta' => null,
            'notes' => 'Updated',
            'lines' => [
                [
                    'id' => $po->items[0]->id,
                    'item_id' => $po->items[0]->item_id,
                    'uom' => 'PCS',
                    'qty_ordered' => (float) $po->items[0]->ordered_qty + 1,
                ],
                [
                    'item_id' => $item->id,
                    'uom' => 'PCS',
                    'qty_ordered' => 3,
                ],
            ],
        ])
        ->assertRedirect();

    $po->refresh();
    expect($po->status)->toBe('approved');
    expect($po->items()->count())->toBe(2);
});
