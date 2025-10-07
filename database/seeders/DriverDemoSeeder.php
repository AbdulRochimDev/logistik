<?php

namespace Database\Seeders;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\DriverAssignment;
use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;
use App\Domain\Outbound\Models\Vehicle;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverDemoSeeder extends Seeder
{
    public function run(): void
    {
        $driverEmail = config('wms.auth.demo_driver_email', 'driver.demo@example.com');
        $driverPassword = config('wms.auth.demo_driver_password', 'password');

        $driverUser = User::firstOrCreate(
            ['email' => $driverEmail],
            [
                'name' => 'Demo Driver',
                'password' => Hash::make($driverPassword),
            ]
        );

        $driverRole = \App\Domain\Auth\Models\Role::firstOrCreate(
            ['name' => 'driver'],
            ['description' => 'Delivery driver']
        );

        $driverUser->roles()->syncWithoutDetaching([$driverRole->id]);

        $driver = Driver::updateOrCreate(
            ['user_id' => $driverUser->id],
            [
                'name' => $driverUser->name,
                'phone' => '089900112233',
                'license_no' => 'DRV-DEMO',
                'status' => 'active',
            ]
        );

        $vehicle = Vehicle::updateOrCreate(
            ['plate_no' => 'B 9001 DEM'],
            [
                'type' => 'Box Truck',
                'capacity' => 4000,
                'status' => 'active',
            ]
        );

        $warehouse = Warehouse::where('code', 'WH1')->first();
        if (! $warehouse) {
            $this->warnConsole('Warehouse WH1 not found, skipping DriverDemoSeeder.');

            return;
        }

        $outbound = OutboundShipment::first();
        if (! $outbound) {
            $this->warnConsole('Outbound shipment not found, skipping DriverDemoSeeder.');

            return;
        }

        $shipment = Shipment::updateOrCreate(
            ['shipment_no' => 'SHP-DEMO-01'],
            [
                'outbound_shipment_id' => $outbound->id,
                'warehouse_id' => $warehouse->id,
                'tracking_no' => 'TRK-DEMO-01',
                'status' => 'allocated',
                'planned_at' => now()->addDay(),
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
            ]
        );

        $lotItem = Item::where('sku', 'SKU-LOT-01')->first();
        $stdItem = Item::where('sku', 'SKU-STD-02')->first();
        $lot = ItemLot::where('lot_no', 'LOT-01')->first();
        $rackA1 = Location::where('code', 'RACK-A1')->first();
        $rackA2 = Location::where('code', 'RACK-A2')->first();

        if (! $lotItem || ! $stdItem || ! $lot || ! $rackA1 || ! $rackA2) {
            $this->warnConsole('Core inventory data missing, skipping DriverDemoSeeder.');

            return;
        }

        $lotSoItem = SoItem::where('item_id', $lotItem->id)->first();
        $stdSoItem = SoItem::where('item_id', $stdItem->id)->first();

        ShipmentItem::updateOrCreate([
            'shipment_id' => $shipment->id,
            'so_item_id' => $lotSoItem?->id,
        ], [
            'item_id' => $lotItem->id,
            'item_lot_id' => $lot->id,
            'from_location_id' => $rackA1->id,
            'qty_planned' => 4,
            'qty_picked' => 0,
            'qty_shipped' => 0,
            'qty_delivered' => 0,
        ]);

        ShipmentItem::updateOrCreate([
            'shipment_id' => $shipment->id,
            'so_item_id' => $stdSoItem?->id,
        ], [
            'item_id' => $stdItem->id,
            'item_lot_id' => null,
            'from_location_id' => $rackA2->id,
            'qty_planned' => 6,
            'qty_picked' => 0,
            'qty_shipped' => 0,
            'qty_delivered' => 0,
        ]);

        DriverAssignment::updateOrCreate([
            'driver_id' => $driver->id,
            'shipment_id' => $shipment->id,
        ], [
            'assigned_at' => now(),
        ]);
    }

    private function warnConsole(string $message): void
    {
        /** @var Command|null $command */
        $command = $this->command;

        if ($command instanceof Command) {
            $command->warn($message);
        }
    }
}
