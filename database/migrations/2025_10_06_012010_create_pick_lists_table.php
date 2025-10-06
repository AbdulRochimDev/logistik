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

        Schema::create('pick_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_shipment_id')->constrained();
            $table->string('picklist_no')->unique();
            $table->foreignId('picker_id')->constrained('users');
            $table->enum('status', ["draft","in_progress","completed"])->default('draft');
            $table->dateTime('started_at')->nullable();
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
        Schema::dropIfExists('pick_lists');
    }
};
