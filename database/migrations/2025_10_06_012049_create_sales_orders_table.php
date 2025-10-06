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

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('so_no')->unique();
            $table->enum('status', ["draft","approved","allocated","picked","shipped","completed"])->default('draft');
            $table->date('ship_by')->nullable();
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
        Schema::dropIfExists('sales_orders');
    }
};
