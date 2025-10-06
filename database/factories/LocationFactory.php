<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'code' => $this->faker->unique()->lexify('LOC-???'),
            'name' => ucfirst($this->faker->word()) . ' Rack',
            'type' => $this->faker->randomElement(['bulk', 'pick', 'staging']),
            'is_default' => false,
        ];
    }
}
