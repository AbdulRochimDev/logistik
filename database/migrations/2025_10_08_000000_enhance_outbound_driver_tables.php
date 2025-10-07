<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('license_no', 100)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index('status', 'ix_drivers_status');
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('plate_no', 50)->unique();
            $table->string('type', 100)->nullable();
            $table->decimal('capacity', 16, 3)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index('status', 'ix_vehicles_status');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->string('shipment_no', 100)->nullable()->unique('ux_shipments_shipment_no');
            $table->foreignId('warehouse_id')->nullable()->after('outbound_shipment_id')->constrained('warehouses');
            $table->string('status', 30)->default('draft')->after('tracking_no');
            $table->dateTime('planned_at')->nullable()->after('status');
            $table->foreignId('driver_id')->nullable()->after('planned_at')->constrained('drivers');
            $table->foreignId('vehicle_id')->nullable()->after('driver_id')->constrained('vehicles');
        });

        Schema::create('shipment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('so_item_id')->nullable()->constrained('so_items');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_lot_id')->nullable()->constrained('item_lots');
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->decimal('qty_planned', 16, 3);
            $table->decimal('qty_picked', 16, 3)->default(0);
            $table->decimal('qty_shipped', 16, 3)->default(0);
            $table->decimal('qty_delivered', 16, 3)->default(0);
            $table->timestamps();
            $table->unique(['shipment_id', 'item_id', 'item_lot_id'], 'ux_shipment_items_unique_bucket');
            $table->index(['shipment_id', 'so_item_id'], 'ix_shipment_items_lookup');
        });

        Schema::create('driver_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->dateTime('assigned_at');
            $table->timestamps();
            $table->unique(['driver_id', 'shipment_id'], 'ux_driver_assignments_driver_shipment');
        });

        Schema::table('pods', function (Blueprint $table): void {
            if (! Schema::hasColumn('pods', 'meta')) {
                $table->json('meta')->nullable()->after('signature_path');
            }

            if (! Schema::hasColumn('pods', 'signer_id')) {
                $table->string('signer_id', 100)->nullable()->after('signed_by');
            }

            $table->unique('shipment_id', 'ux_pods_shipment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pods', function (Blueprint $table): void {
            $table->dropUnique('ux_pods_shipment_unique');
            if (Schema::hasColumn('pods', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('pods', 'signer_id')) {
                $table->dropColumn('signer_id');
            }
        });

        Schema::dropIfExists('driver_assignments');
        Schema::dropIfExists('shipment_items');

        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropUnique('ux_shipments_shipment_no');
            $table->dropColumn(['shipment_no', 'status', 'planned_at']);
            if (Schema::hasColumn('shipments', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
            if (Schema::hasColumn('shipments', 'driver_id')) {
                $table->dropConstrainedForeignId('driver_id');
            }
            if (Schema::hasColumn('shipments', 'vehicle_id')) {
                $table->dropConstrainedForeignId('vehicle_id');
            }
        });

        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
    }
};
