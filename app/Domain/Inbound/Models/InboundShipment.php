<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property string|null $asn_no
 * @property string $status
 * @property \Carbon\CarbonInterface|null $scheduled_at
 * @property string|null $remarks
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GrnHeader> $grns
 * @property-read int|null $grns_count
 *
 * @method static \Database\Factories\InboundShipmentFactory newFactory()
 */
class InboundShipment extends Model
{
    use HasFactory;

    protected $table = 'inbound_shipments';

    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function grns(): HasMany
    {
        return $this->hasMany(GrnHeader::class, 'inbound_shipment_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\InboundShipmentFactory::new();
    }
}
