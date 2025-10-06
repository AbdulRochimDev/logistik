<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->decimal('qty_on_hand', 16, 3)->default(0);
            $table->decimal('qty_allocated', 16, 3)->default(0);
            $table->decimal('qty_available', 16, 3)->storedAs('qty_on_hand - qty_allocated');
            $table->timestamps();
            $table->unique(['warehouse_id', 'location_id', 'item_id', 'item_lot_id'], 'ux_stocks_unique_bucket');
            $table->index(['warehouse_id', 'location_id', 'item_id', 'item_lot_id'], 'ix_stocks_lookup');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks');
            $table->string('type');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->decimal('quantity', 16, 3);
            $table->string('uom', 20);
            $table->string('ref_type');
            $table->string('ref_id');
            $table->foreignId('actor_user_id')->nullable()->constrained('users');
            $table->string('remarks')->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->unique(['ref_type', 'ref_id', 'type'], 'ux_movements_ref_type_id_type');
            $table->index(['item_id', 'item_lot_id', 'from_location_id', 'to_location_id'], 'ix_movements_item_lot_from_to');
        });

        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('adjustment_no')->unique();
            $table->string('type');
            $table->string('reason');
            $table->string('status');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('adjustments')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->foreignId('location_id')->constrained('locations');
            $table->decimal('quantity_diff', 16, 3);
            $table->timestamps();
        });

        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('cycle_no')->unique();
            $table->string('status');
            $table->timestamp('scheduled_for')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_count_id')->constrained('cycle_counts')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->decimal('system_qty', 16, 3);
            $table->decimal('counted_qty', 16, 3);
            $table->decimal('variance_qty', 16, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
        Schema::dropIfExists('cycle_counts');
        Schema::dropIfExists('adjustment_lines');
        Schema::dropIfExists('adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
    }
};
