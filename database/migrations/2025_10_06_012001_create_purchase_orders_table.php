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

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('po_no')->unique();
            $table->enum('status', ["draft","approved","closed"])->default('draft');
            $table->date('eta')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users', 'by');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'by');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
