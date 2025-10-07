<?php

namespace App\Domain\Inbound\Models;

use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $grn_header_id
 * @property int $po_item_id
 * @property int|null $item_lot_id
 * @property int|null $putaway_location_id
 * @property float $received_qty
 * @property float $rejected_qty
 * @property string $uom
 * @property-read GrnHeader $header
 * @property-read PoItem $poItem
 * @property-read ItemLot|null $lot
 * @property-read Location|null $location
 *
 * @method static \Database\Factories\GrnLineFactory newFactory()
 */
class GrnLine extends Model
{
    use HasFactory;

    protected $table = 'grn_lines';

    protected $guarded = [];

    protected $casts = [
        'received_qty' => 'float',
        'rejected_qty' => 'float',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(GrnHeader::class, 'grn_header_id');
    }

    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PoItem::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'item_lot_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'putaway_location_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\GrnLineFactory::new();
    }
}
