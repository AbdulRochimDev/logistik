<?php

namespace Database\Factories;

use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoItemFactory extends Factory
{
    protected $model = PoItem::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'item_id' => Item::factory(),
            'ordered_qty' => $this->faker->numberBetween(1, 20),
            'received_qty' => 0,
            'uom' => 'PCS',
        ];
    }
}
