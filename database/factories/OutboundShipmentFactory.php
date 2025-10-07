<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundShipment>
 */
class OutboundShipmentFactory extends Factory
{
    protected $model = OutboundShipment::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'wave_no' => $this->faker->optional()->bothify('WAVE-###'),
            'status' => 'created',
            'dispatched_at' => null,
            'delivered_at' => null,
        ];
    }
}
