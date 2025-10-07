<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\DriverAssignment;
use App\Domain\Outbound\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverAssignment>
 */
class DriverAssignmentFactory extends Factory
{
    protected $model = DriverAssignment::class;

    public function definition(): array
    {
        return [
            'driver_id' => Driver::factory(),
            'shipment_id' => Shipment::factory(),
            'assigned_at' => now(),
        ];
    }
}
