<?php

namespace App\Domain\Outbound\Models;

use App\Domain\Inventory\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sales_order_id
 * @property int $item_id
 * @property float $ordered_qty
 * @property float $allocated_qty
 * @property string $uom
 * @property-read Item|null $item
 * @property-read SalesOrder|null $salesOrder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PickLine> $pickLines
 * @property-read int|null $pick_lines_count
 *
 * @method static \Database\Factories\SoItemFactory newFactory()
 */
class SoItem extends Model
{
    use HasFactory;

    protected $table = 'so_items';

    protected $guarded = [];

    protected $casts = [
        'ordered_qty' => 'float',
        'allocated_qty' => 'float',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function pickLines(): HasMany
    {
        return $this->hasMany(PickLine::class, 'so_item_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\SoItemFactory::new();
    }
}
