<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unique(['ref_type', 'ref_id', 'type'], 'ux_stock_movements_ref');
            $table->index(['item_id', 'item_lot_id', 'from_location_id', 'to_location_id'], 'ix_stock_movements_item_flow');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('ux_stock_movements_ref');
            $table->dropIndex('ix_stock_movements_item_flow');
        });
    }
};
