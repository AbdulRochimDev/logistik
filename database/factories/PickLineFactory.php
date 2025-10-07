<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Location;
use App\Domain\Outbound\Models\PickLine;
use App\Domain\Outbound\Models\PickList;
use App\Domain\Outbound\Models\SoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickLine>
 */
class PickLineFactory extends Factory
{
    protected $model = PickLine::class;

    public function definition(): array
    {
        return [
            'pick_list_id' => PickList::factory(),
            'so_item_id' => SoItem::factory(),
            'item_lot_id' => null,
            'from_location_id' => Location::factory(),
            'picked_qty' => $this->faker->numberBetween(1, 10),
            'confirmed_by' => null,
        ];
    }
}
