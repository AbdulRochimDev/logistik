<?php

namespace App\Domain\Outbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $sales_order_id
 * @property string $status
 * @property \Carbon\CarbonInterface|null $dispatched_at
 * @property \Carbon\CarbonInterface|null $delivered_at
 * @property-read SalesOrder|null $salesOrder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PickList> $pickLists
 * @property-read Shipment|null $shipment
 *
 * @method static \Database\Factories\OutboundShipmentFactory newFactory()
 */
class OutboundShipment extends Model
{
    use HasFactory;

    protected $table = 'outbound_shipments';

    protected $guarded = [];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function pickLists(): HasMany
    {
        return $this->hasMany(PickList::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\OutboundShipmentFactory::new();
    }
}
