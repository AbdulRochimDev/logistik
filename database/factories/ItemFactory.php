<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-###??')),
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->sentence(),
            'default_uom' => 'PCS',
            'is_lot_tracked' => false,
        ];
    }
}
