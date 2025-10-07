<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Item;
use App\Domain\Outbound\Models\SalesOrder;
use App\Domain\Outbound\Models\SoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoItem>
 */
class SoItemFactory extends Factory
{
    protected $model = SoItem::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'item_id' => Item::factory(),
            'uom' => 'PCS',
            'ordered_qty' => $this->faker->numberBetween(1, 20),
            'allocated_qty' => 0,
        ];
    }
}
