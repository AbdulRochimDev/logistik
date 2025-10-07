<?php

namespace App\Domain\Outbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $plate_no
 * @property string|null $type
 * @property float|null $capacity
 * @property string $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Shipment> $shipments
 * @property-read int|null $shipments_count
 *
 * @method static \Database\Factories\VehicleFactory newFactory()
 */
class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $guarded = [];

    protected $casts = [
        'capacity' => 'float',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\VehicleFactory::new();
    }
}
