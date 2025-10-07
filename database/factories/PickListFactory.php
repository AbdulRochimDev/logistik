<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\PickList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickList>
 */
class PickListFactory extends Factory
{
    protected $model = PickList::class;

    public function definition(): array
    {
        return [
            'outbound_shipment_id' => OutboundShipment::factory(),
            'picklist_no' => strtoupper($this->faker->unique()->bothify('PICK-####')),
            'picker_id' => User::factory(),
            'status' => 'open',
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
