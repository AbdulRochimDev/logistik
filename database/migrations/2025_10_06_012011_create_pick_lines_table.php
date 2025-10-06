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

        Schema::create('pick_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_list_id')->constrained();
            $table->foreignId('so_item_id')->constrained();
            $table->foreignId('item_lot_id')->nullable()->constrained();
            $table->foreignId('from_location_id')->constrained('locations');
            $table->decimal('picked_qty', 16, 3);
            $table->foreignId('confirmed_by')->nullable()->constrained('users', 'by');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_lines');
    }
};
