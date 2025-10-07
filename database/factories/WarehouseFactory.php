<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('WH-???'),
            'name' => $this->faker->company().' Warehouse',
            'address' => $this->faker->address(),
        ];
    }
}
