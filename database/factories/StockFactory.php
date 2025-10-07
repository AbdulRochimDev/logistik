<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'location_id' => function (array $attributes) {
                return Location::factory()
                    ->create(['warehouse_id' => $attributes['warehouse_id']])
                    ->id;
            },
            'item_id' => Item::factory(),
            'item_lot_id' => null,
            'qty_on_hand' => 0,
            'qty_allocated' => 0,
        ];
    }
}
