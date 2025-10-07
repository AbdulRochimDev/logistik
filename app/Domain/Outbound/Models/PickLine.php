<?php

namespace App\Domain\Outbound\Models;

use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pick_list_id
 * @property int $so_item_id
 * @property int $from_location_id
 * @property float $picked_qty
 * @property-read PickList $pickList
 * @property-read SoItem $soItem
 */
class PickLine extends Model
{
    use HasFactory;

    protected $table = 'pick_lines';

    protected $guarded = [];

    protected $casts = [
        'picked_qty' => 'float',
    ];

    public function pickList(): BelongsTo
    {
        return $this->belongsTo(PickList::class);
    }

    public function soItem(): BelongsTo
    {
        return $this->belongsTo(SoItem::class, 'so_item_id');
    }

    public function itemLot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    protected static function newFactory()
    {
        return \Database\Factories\PickLineFactory::new();
    }
}
