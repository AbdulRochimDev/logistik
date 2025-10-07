<?php

namespace App\Domain\Outbound\Models;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shipment_id
 * @property int|null $so_item_id
 * @property int $item_id
 * @property int|null $item_lot_id
 * @property int|null $from_location_id
 * @property float $qty_planned
 * @property float $qty_picked
 * @property float $qty_shipped
 * @property float $qty_delivered
 * @property-read Shipment $shipment
 * @property-read SoItem|null $salesOrderItem
 * @property-read Item $item
 * @property-read ItemLot|null $lot
 * @property-read \App\Domain\Inventory\Models\Location|null $fromLocation
 *
 * @method static \Database\Factories\ShipmentItemFactory newFactory()
 */
class ShipmentItem extends Model
{
    use HasFactory;

    protected $table = 'shipment_items';

    protected $guarded = [];

    protected $casts = [
        'qty_planned' => 'float',
        'qty_picked' => 'float',
        'qty_shipped' => 'float',
        'qty_delivered' => 'float',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SoItem::class, 'so_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'item_lot_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\ShipmentItemFactory::new();
    }
}
