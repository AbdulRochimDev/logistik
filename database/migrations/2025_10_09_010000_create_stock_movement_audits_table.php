<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movement_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('movement_id')->unique('ux_stock_movement_audits_movement');
            $table->string('context', 80);
            $table->string('type', 60);
            $table->string('ref_type', 100);
            $table->string('ref_id', 150);
            $table->string('warehouse_code', 50);
            $table->string('location_code', 100)->nullable();
            $table->string('sku', 150)->nullable();
            $table->decimal('qty_on_hand', 16, 3);
            $table->decimal('qty_allocated', 16, 3);
            $table->decimal('quantity', 16, 3);
            $table->timestamp('moved_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'warehouse_code'], 'ix_stock_movement_audits_type_wh');
            $table->index('moved_at', 'ix_stock_movement_audits_moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_audits');
    }
};
