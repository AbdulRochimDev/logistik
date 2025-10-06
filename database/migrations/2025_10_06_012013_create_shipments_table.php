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

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_shipment_id')->constrained();
            $table->string('carrier')->nullable();
            $table->string('tracking_no')->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
