<?php

namespace App\Domain\Outbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $driver_id
 * @property int $shipment_id
 * @property \Carbon\CarbonInterface $assigned_at
 * @property-read Driver $driver
 * @property-read Shipment $shipment
 *
 * @method static \Database\Factories\DriverAssignmentFactory newFactory()
 */
class DriverAssignment extends Model
{
    use HasFactory;

    protected $table = 'driver_assignments';

    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\DriverAssignmentFactory::new();
    }
}
