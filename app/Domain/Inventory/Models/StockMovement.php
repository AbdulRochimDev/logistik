<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stock_id
 * @property string $type
 * @property string $ref_type
 * @property string $ref_id
 * @property float $quantity
 * @property string $uom
 * @property int|null $warehouse_id
 * @property int|null $item_id
 * @property int|null $item_lot_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property int|null $actor_user_id
 * @property string|null $remarks
 * @property \Carbon\CarbonInterface|null $moved_at
 * @property-read Stock $stock
 * @property-read Warehouse|null $warehouse
 * @property-read Item|null $item
 * @property-read ItemLot|null $lot
 *
 * @method static \Database\Factories\StockFactory newFactory()
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'stock_id',
        'type',
        'warehouse_id',
        'item_id',
        'item_lot_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'uom',
        'ref_type',
        'ref_id',
        'actor_user_id',
        'remarks',
        'moved_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'moved_at' => 'datetime',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'item_lot_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\StockFactory::new();
    }
}
