<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->string('uom');
            $table->decimal('ordered_qty', 16, 3);
            $table->decimal('received_qty', 16, 3)->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_items');
    }
};
