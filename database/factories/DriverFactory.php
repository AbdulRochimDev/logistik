<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'license_no' => strtoupper($this->faker->bothify('DRV####')),
            'status' => 'active',
        ];
    }
}
