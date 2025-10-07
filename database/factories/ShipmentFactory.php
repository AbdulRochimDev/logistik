<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'outbound_shipment_id' => OutboundShipment::factory(),
            'warehouse_id' => null,
            'shipment_no' => strtoupper($this->faker->unique()->bothify('SHP####')),
            'carrier' => $this->faker->company(),
            'tracking_no' => strtoupper($this->faker->unique()->bothify('TRK#######')),
            'status' => 'draft',
            'planned_at' => now(),
            'driver_id' => null,
            'vehicle_id' => null,
            'dispatched_at' => null,
            'delivered_at' => null,
        ];
    }
}
