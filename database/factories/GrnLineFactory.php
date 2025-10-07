<?php

namespace Database\Factories;

use App\Domain\Inbound\Models\GrnHeader;
use App\Domain\Inbound\Models\GrnLine;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inventory\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrnLineFactory extends Factory
{
    protected $model = GrnLine::class;

    public function definition(): array
    {
        return [
            'grn_header_id' => GrnHeader::factory(),
            'po_item_id' => PoItem::factory(),
            'putaway_location_id' => Location::factory(),
            'received_qty' => $this->faker->numberBetween(1, 10),
            'rejected_qty' => 0,
            'uom' => 'PCS',
        ];
    }
}
