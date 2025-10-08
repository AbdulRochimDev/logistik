<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index(['warehouse_id', 'type', 'moved_at'], 'ix_stock_movements_wh_type_moved_at');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex('ix_stock_movements_wh_type_moved_at');
        });
    }
};
