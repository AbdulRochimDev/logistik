<?php

namespace Database\Factories;

use App\Domain\Inbound\Models\GrnHeader;
use App\Domain\Inbound\Models\InboundShipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrnHeaderFactory extends Factory
{
    protected $model = GrnHeader::class;

    public function definition(): array
    {
        return [
            'inbound_shipment_id' => InboundShipment::factory(),
            'grn_no' => $this->faker->unique()->numerify('GRN#####'),
            'received_at' => now(),
            'status' => 'draft',
            'received_by' => User::factory(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
