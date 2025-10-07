<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\Role;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inbound\Models\Supplier;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Customer;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\DriverAssignment;
use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\PickLine;
use App\Domain\Outbound\Models\PickList;
use App\Domain\Outbound\Models\SalesOrder;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\SoItem;
use App\Domain\Outbound\Models\Vehicle;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LogisticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin_gudang'],
            ['description' => 'Warehouse administrator']
        );

        $driverRole = Role::query()->firstOrCreate(
            ['name' => 'driver'],
            ['description' => 'Delivery driver']
        );

        $adminPassword = config('wms.auth.admin_password', 'ChangeMe!123');
        $driverPassword = config('wms.auth.driver_password', 'password');

        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Gudang',
                'password' => Hash::make($adminPassword),
            ]
        );

        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        $driverUser = User::query()->updateOrCreate(
            ['email' => 'driver@example.com'],
            [
                'name' => 'Driver One',
                'password' => Hash::make($driverPassword),
            ]
        );

        $driverUser->roles()->syncWithoutDetaching([$driverRole->id]);

        $warehouse = Warehouse::query()->firstOrCreate(
            ['code' => 'WH1'],
            ['name' => 'Primary Warehouse', 'address' => 'Default warehouse']
        );

        $locations = collect([
            'STAGING' => 'staging',
            'RACK-A1' => 'pick',
            'RACK-A2' => 'pick',
            'OUTBOUND' => 'staging',
        ])->mapWithKeys(function (string $type, string $code) use ($warehouse) {
            $location = Location::query()->updateOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $code],
                [
                    'name' => Str::title(str_replace('-', ' ', strtolower($code))),
                    'type' => $type,
                    'is_default' => $code === 'STAGING',
                ]
            );

            return [$code => $location];
        });

        $lotItem = Item::query()->updateOrCreate(
            ['sku' => 'SKU-LOT-01'],
            ['name' => 'Lot Tracked Widget', 'default_uom' => 'PCS', 'is_lot_tracked' => true]
        );

        $stdItem = Item::query()->updateOrCreate(
            ['sku' => 'SKU-STD-02'],
            ['name' => 'Standard Widget', 'default_uom' => 'PCS', 'is_lot_tracked' => false]
        );

        $lot = ItemLot::query()->updateOrCreate(
            ['item_id' => $lotItem->id, 'lot_no' => 'LOT-01'],
            ['production_date' => now()->subMonth(), 'expiry_date' => now()->addMonths(6)]
        );

        $supplier = Supplier::updateOrCreate(
            ['code' => 'SUP-001'],
            [
                'name' => 'Acme Supplies',
                'contact_name' => 'Jane Doe',
                'phone' => '08123456789',
                'email' => 'supplier@example.com',
            ]
        );

        $purchaseOrder = PurchaseOrder::updateOrCreate(
            ['po_no' => 'PO-1001'],
            [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'approved',
                'eta' => now()->addWeek(),
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
            ]
        );

        $lotPoItem = PoItem::updateOrCreate([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => $lotItem->id,
        ], [
            'uom' => 'PCS',
            'ordered_qty' => 20,
            'received_qty' => 0,
        ]);

        $stdPoItem = PoItem::updateOrCreate([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => $stdItem->id,
        ], [
            'uom' => 'PCS',
            'ordered_qty' => 15,
            'received_qty' => 0,
        ]);

        $customer = Customer::updateOrCreate([
            'code' => 'CUST-001',
            'name' => 'PT Pelanggan Setia',
        ], [
            'phone' => '021-555-1234',
            'email' => 'customer@example.com',
            'address' => 'Jl. Pelanggan No.2, Jakarta',
        ]);

        $salesOrder = SalesOrder::updateOrCreate([
            'so_no' => 'SO-2001',
        ], [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
            'ship_by' => now()->addDays(2),
            'notes' => 'Priority delivery',
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
        ]);

        $lotSoItem = SoItem::updateOrCreate([
            'sales_order_id' => $salesOrder->id,
            'item_id' => $lotItem->id,
        ], [
            'uom' => 'PCS',
            'ordered_qty' => 10,
            'allocated_qty' => 0,
        ]);

        $stdSoItem = SoItem::updateOrCreate([
            'sales_order_id' => $salesOrder->id,
            'item_id' => $stdItem->id,
        ], [
            'uom' => 'PCS',
            'ordered_qty' => 20,
            'allocated_qty' => 0,
        ]);

        $outboundShipment = OutboundShipment::updateOrCreate([
            'sales_order_id' => $salesOrder->id,
        ], [
            'wave_no' => 'WAVE-1001',
            'status' => 'created',
        ]);

        $pickList = PickList::updateOrCreate(
            [
                'outbound_shipment_id' => $outboundShipment->id,
                'picklist_no' => 'PICK-5001',
            ],
            [
                'picker_id' => $adminUser->id,
                'status' => 'open',
            ]
        );

        PickLine::updateOrCreate(
            [
                'pick_list_id' => $pickList->id,
                'so_item_id' => $lotSoItem->id,
            ],
            [
                'item_lot_id' => $lot->id,
                'from_location_id' => $locations['RACK-A1']->id,
                'picked_qty' => 0,
                'confirmed_by' => null,
            ]
        );

        PickLine::updateOrCreate(
            [
                'pick_list_id' => $pickList->id,
                'so_item_id' => $stdSoItem->id,
            ],
            [
                'item_lot_id' => null,
                'from_location_id' => $locations['RACK-A2']->id,
                'picked_qty' => 0,
                'confirmed_by' => null,
            ]
        );

        $driver = Driver::updateOrCreate([
            'user_id' => $driverUser->id,
        ], [
            'name' => $driverUser->name,
            'phone' => '082233445566',
            'license_no' => 'DRV-001',
            'status' => 'active',
        ]);

        $vehicle = Vehicle::updateOrCreate([
            'plate_no' => 'B 1234 WMS',
        ], [
            'type' => 'Box Truck',
            'capacity' => 3500,
            'status' => 'active',
        ]);

        $shipment = Shipment::updateOrCreate([
            'outbound_shipment_id' => $outboundShipment->id,
        ], [
            'warehouse_id' => $warehouse->id,
            'shipment_no' => 'SHP-7001',
            'carrier' => 'Internal Fleet',
            'tracking_no' => 'TRK-5001',
            'status' => 'allocated',
            'planned_at' => now()->addDay(),
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        ShipmentItem::updateOrCreate([
            'shipment_id' => $shipment->id,
            'so_item_id' => $lotSoItem->id,
        ], [
            'item_id' => $lotItem->id,
            'item_lot_id' => $lot->id,
            'from_location_id' => $locations['RACK-A1']->id,
            'qty_planned' => 6,
            'qty_picked' => 0,
            'qty_shipped' => 0,
            'qty_delivered' => 0,
        ]);

        ShipmentItem::updateOrCreate([
            'shipment_id' => $shipment->id,
            'so_item_id' => $stdSoItem->id,
        ], [
            'item_id' => $stdItem->id,
            'item_lot_id' => null,
            'from_location_id' => $locations['RACK-A2']->id,
            'qty_planned' => 8,
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
