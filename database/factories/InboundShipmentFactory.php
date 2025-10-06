<?php

namespace Database\Factories;

use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inbound\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class InboundShipmentFactory extends Factory
{
    protected $model = InboundShipment::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'asn_no' => $this->faker->optional()->numerify('ASN#####'),
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+2 days'),
            'remarks' => $this->faker->sentence(),
        ];
    }
}
