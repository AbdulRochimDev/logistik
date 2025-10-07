<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $location_id
 * @property int $item_id
 * @property int|null $item_lot_id
 * @property float $qty_on_hand
 * @property float $qty_allocated
 * @property float $qty_available
 * @property-read Warehouse $warehouse
 * @property-read Location $location
 * @property-read Item $item
 * @property-read ItemLot|null $lot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMovement> $movements
 * @property-read int|null $movements_count
 *
 * @method static \Database\Factories\StockFactory newFactory()
 */
class Stock extends Model
{
    use HasFactory;

    protected $table = 'stocks';

    protected $fillable = [
        'warehouse_id',
        'location_id',
        'item_id',
        'item_lot_id',
        'qty_on_hand',
        'qty_allocated',
    ];

    protected $casts = [
        'warehouse_id' => 'int',
        'location_id' => 'int',
        'item_id' => 'int',
        'item_lot_id' => 'int',
        'qty_on_hand' => 'float',
        'qty_allocated' => 'float',
        'qty_available' => 'float',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'item_lot_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\StockFactory::new();
    }
}
