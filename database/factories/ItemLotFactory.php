<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemLotFactory extends Factory
{
    protected $model = ItemLot::class;

    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'lot_no' => strtoupper($this->faker->unique()->bothify('LOT-####')),
            'production_date' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
