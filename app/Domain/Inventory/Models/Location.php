<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_default
 * @property-read Warehouse $warehouse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stock> $stocks
 *
 * @method static \Database\Factories\LocationFactory newFactory()
 */
class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'bool',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\LocationFactory::new();
    }
}
