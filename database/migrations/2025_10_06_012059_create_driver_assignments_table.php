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

        Schema::create('driver_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_no')->unique();
            $table->foreignId('driver_profile_id')->constrained();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('outbound_shipment_id')->constrained();
            $table->dateTime('assigned_at');
            $table->enum('status', ["assigned","en_route","delivered","closed"])->default('assigned');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_assignments');
    }
};
