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

        Schema::create('inbound_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained();
            $table->string('asn_no')->nullable();
            $table->enum('status', ["scheduled","arrived","received","closed"])->default('scheduled');
            $table->dateTime('scheduled_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_shipments');
    }
};
