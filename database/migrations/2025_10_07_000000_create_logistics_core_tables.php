<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id'], 'ux_user_roles_user_role');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->string('type', 50);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code'], 'ux_locations_warehouse_code');
            $table->index(['warehouse_id', 'type'], 'ix_locations_type_lookup');
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_uom', 20);
            $table->boolean('is_lot_tracked')->default(false);
            $table->timestamps();
        });

        Schema::create('item_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('lot_no', 100);
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->unique(['item_id', 'lot_no'], 'ux_item_lots_item_lot');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('po_no', 100)->unique();
            $table->string('status', 30)->default('draft');
            $table->date('eta')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['supplier_id', 'warehouse_id'], 'ix_purchase_orders_supplier_wh');
        });

        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->string('uom', 20);
            $table->decimal('ordered_qty', 16, 3);
            $table->decimal('received_qty', 16, 3)->default(0);
            $table->timestamps();
            $table->index(['purchase_order_id', 'item_id'], 'ix_po_items_lookup');
        });

        Schema::create('inbound_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('asn_no', 100)->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->dateTime('scheduled_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['purchase_order_id', 'asn_no'], 'ux_inbound_shipments_po_asn');
        });

        Schema::create('grn_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_shipment_id')->constrained('inbound_shipments')->cascadeOnDelete();
            $table->string('grn_no', 100)->unique();
            $table->dateTime('received_at');
            $table->string('status', 30)->default('draft');
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_header_id')->constrained('grn_headers')->cascadeOnDelete();
            $table->foreignId('po_item_id')->constrained('po_items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->foreignId('putaway_location_id')->nullable()->constrained('locations');
            $table->decimal('received_qty', 16, 3);
            $table->decimal('rejected_qty', 16, 3)->default(0);
            $table->string('uom', 20);
            $table->timestamps();
            $table->unique(['grn_header_id', 'po_item_id', 'item_lot_id', 'putaway_location_id'], 'ux_grn_lines_unique');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('so_no', 100)->unique();
            $table->string('status', 30)->default('draft');
            $table->date('ship_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['customer_id', 'warehouse_id'], 'ix_sales_orders_customer_wh');
        });

        Schema::create('so_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->string('uom', 20);
            $table->decimal('ordered_qty', 16, 3);
            $table->decimal('allocated_qty', 16, 3)->default(0);
            $table->timestamps();
            $table->index(['sales_order_id', 'item_id'], 'ix_so_items_lookup');
        });

        Schema::create('outbound_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('wave_no', 100)->nullable();
            $table->string('status', 30)->default('created');
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pick_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_shipment_id')->constrained('outbound_shipments')->cascadeOnDelete();
            $table->string('picklist_no', 100)->unique();
            $table->foreignId('picker_id')->constrained('users');
            $table->string('status', 30)->default('open');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pick_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_list_id')->constrained('pick_lists')->cascadeOnDelete();
            $table->foreignId('so_item_id')->constrained('so_items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->foreignId('from_location_id')->constrained('locations');
            $table->decimal('picked_qty', 16, 3);
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['pick_list_id', 'so_item_id'], 'ix_pick_lines_lookup');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_shipment_id')->constrained('outbound_shipments')->cascadeOnDelete();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_no', 191)->nullable()->unique();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('signed_by');
            $table->dateTime('signed_at');
            $table->string('photo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('external_idempotency_key')->nullable()->unique('ux_pods_external_idem');
            $table->timestamps();
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->decimal('qty_on_hand', 16, 3)->default(0);
            $table->decimal('qty_allocated', 16, 3)->default(0);
            $table->decimal('qty_available', 16, 3)->storedAs('`qty_on_hand` - `qty_allocated`');
            $table->timestamps();
            $table->unique(['warehouse_id', 'location_id', 'item_id', 'item_lot_id'], 'ux_stocks_bucket');
            $table->index(['item_id', 'warehouse_id'], 'ix_stocks_item_warehouse');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks');
            $table->string('type', 50);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->decimal('quantity', 16, 3);
            $table->string('uom', 20);
            $table->string('ref_type', 100);
            $table->string('ref_id', 100);
            $table->foreignId('actor_user_id')->nullable()->constrained('users');
            $table->string('remarks', 255)->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pods');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('pick_lines');
        Schema::dropIfExists('pick_lists');
        Schema::dropIfExists('outbound_shipments');
        Schema::dropIfExists('so_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('grn_lines');
        Schema::dropIfExists('grn_headers');
        Schema::dropIfExists('inbound_shipments');
        Schema::dropIfExists('po_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('item_lots');
        Schema::dropIfExists('items');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
