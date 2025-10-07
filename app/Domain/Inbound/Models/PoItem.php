<?php

namespace App\Domain\Inbound\Models;

use App\Domain\Inventory\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $item_id
 * @property string $uom
 * @property float $ordered_qty
 * @property float $received_qty
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Item $item
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GrnLine> $grnLines
 * @property-read int|null $grn_lines_count
 *
 * @method static \Database\Factories\PoItemFactory newFactory()
 */
class PoItem extends Model
{
    use HasFactory;

    protected $table = 'po_items';

    protected $guarded = [];

    protected $casts = [
        'ordered_qty' => 'float',
        'received_qty' => 'float',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLine::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PoItemFactory::new();
    }
}
