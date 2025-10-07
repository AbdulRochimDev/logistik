<?php

namespace Database\Seeders;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiDBInitialSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $warehouse = Warehouse::query()->updateOrCreate(
                ['code' => 'WH1'],
                ['name' => 'Primary Warehouse', 'address' => 'TiDB Cloud default warehouse']
            );

            $locations = collect([
                'STAGING' => 'staging',
                'RACK-A1' => 'pick',
                'RACK-A2' => 'pick',
                'OUTBOUND' => 'staging',
            ])->mapWithKeys(fn (string $type, string $code) => [
                $code => Location::query()->updateOrCreate(
                    ['warehouse_id' => $warehouse->id, 'code' => $code],
                    ['name' => ucwords(str_replace('-', ' ', strtolower($code))), 'type' => $type, 'is_default' => $code === 'STAGING']
                ),
            ]);

            $lotTrackedItem = Item::query()->updateOrCreate(
                ['sku' => 'SKU-LOT-01'],
                ['name' => 'Lot Tracked Widget', 'default_uom' => 'PCS', 'is_lot_tracked' => true]
            );

            $standardItem = Item::query()->updateOrCreate(
                ['sku' => 'SKU-STD-02'],
                ['name' => 'Standard Widget', 'default_uom' => 'PCS', 'is_lot_tracked' => false]
            );

            $lot = ItemLot::query()->updateOrCreate(
                ['item_id' => $lotTrackedItem->id, 'lot_no' => 'LOT-01'],
                ['production_date' => now()->subMonth(), 'expiry_date' => now()->addMonths(6)]
            );

            Stock::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locations['RACK-A1']->id,
                    'item_id' => $lotTrackedItem->id,
                    'item_lot_id' => $lot->id,
                ],
                [
                    'qty_on_hand' => 30,
                    'qty_allocated' => 0,
                ]
            );

            Stock::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locations['RACK-A2']->id,
                    'item_id' => $standardItem->id,
                    'item_lot_id' => null,
                ],
                [
                    'qty_on_hand' => 50,
                    'qty_allocated' => 10,
                ]
            );
        });
    }
}
