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

        Schema::create('outbound_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained();
            $table->string('wave_no')->nullable();
            $table->enum('status', ["pending","allocated","picked","packed","shipped","delivered"])->default('pending');
            $table->dateTime('dispatched_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_shipments');
    }
};
