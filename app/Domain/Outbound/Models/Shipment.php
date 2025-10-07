<?php

namespace App\Domain\Outbound\Models;

use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int|null $warehouse_id
 * @property int|null $driver_id
 * @property int|null $vehicle_id
 * @property string|null $shipment_no
 * @property string|null $carrier
 * @property string|null $tracking_no
 * @property string $status
 * @property \Carbon\CarbonInterface|null $planned_at
 * @property-read OutboundShipment|null $outboundShipment
 * @property-read Driver|null $driver
 * @property-read Vehicle|null $vehicle
 * @property-read Warehouse|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShipmentItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DriverAssignment> $assignments
 *
 * @method static \Database\Factories\ShipmentFactory newFactory()
 */
class Shipment extends Model
{
    use HasFactory;

    protected $table = 'shipments';

    protected $guarded = [];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'planned_at' => 'datetime',
    ];

    public function outboundShipment(): BelongsTo
    {
        return $this->belongsTo(OutboundShipment::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function proofOfDelivery(): HasOne
    {
        return $this->hasOne(Pod::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ShipmentFactory::new();
    }
}
