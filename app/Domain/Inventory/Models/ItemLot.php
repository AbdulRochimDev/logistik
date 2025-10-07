<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $item_id
 * @property string $lot_no
 * @property \Carbon\CarbonInterface|null $production_date
 * @property \Carbon\CarbonInterface|null $expiry_date
 * @property-read Item $item
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stock> $stocks
 *
 * @method static \Database\Factories\ItemLotFactory newFactory()
 */
class ItemLot extends Model
{
    use HasFactory;

    protected $table = 'item_lots';

    protected $guarded = [];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ItemLotFactory::new();
    }
}
