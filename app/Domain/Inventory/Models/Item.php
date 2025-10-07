<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $default_uom
 * @property bool $is_lot_tracked
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ItemLot> $lots
 * @property-read int|null $lots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stock> $stocks
 * @property-read int|null $stocks_count
 *
 * @method static \Database\Factories\ItemFactory newFactory()
 */
class Item extends Model
{
    use HasFactory;

    protected $table = 'items';

    protected $guarded = [];

    protected $casts = [
        'is_lot_tracked' => 'bool',
    ];

    public function lots(): HasMany
    {
        return $this->hasMany(ItemLot::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ItemFactory::new();
    }
}
