<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Location> $locations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stock> $stocks
 *
 * @method static \Database\Factories\WarehouseFactory newFactory()
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $guarded = [];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\WarehouseFactory::new();
    }
}
