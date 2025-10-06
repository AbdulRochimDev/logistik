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

        Schema::create('grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_header_id')->constrained();
            $table->foreignId('po_item_id')->constrained();
            $table->foreignId('item_lot_id')->nullable()->constrained();
            $table->foreignId('putaway_location_id')->nullable()->constrained('locations');
            $table->decimal('received_qty', 16, 3);
            $table->decimal('rejected_qty', 16, 3)->default(0);
            $table->string('uom');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_lines');
    }
};
