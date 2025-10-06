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

        Schema::create('grn_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_shipment_id')->constrained();
            $table->string('grn_no')->unique();
            $table->dateTime('received_at');
            $table->enum('status', ["draft","posted","void"])->default('draft');
            $table->foreignId('received_by')->constrained('users', 'by');
            $table->foreignId('verified_by')->nullable()->constrained('users', 'by');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_headers');
    }
};
