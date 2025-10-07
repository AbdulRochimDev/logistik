<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    protected $model = ShipmentItem::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'so_item_id' => null,
            'item_id' => Item::factory(),
            'item_lot_id' => null,
            'from_location_id' => Location::factory(),
            'qty_planned' => $this->faker->numberBetween(1, 20),
            'qty_picked' => 0,
            'qty_shipped' => 0,
            'qty_delivered' => 0,
        ];
    }
}
