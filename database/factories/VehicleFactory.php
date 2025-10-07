<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'plate_no' => strtoupper($this->faker->unique()->bothify('B ### ?##')),
            'type' => $this->faker->randomElement(['Box Truck', 'Van', 'Motorcycle']),
            'capacity' => $this->faker->randomFloat(1, 500, 5000),
            'status' => 'active',
        ];
    }
}
