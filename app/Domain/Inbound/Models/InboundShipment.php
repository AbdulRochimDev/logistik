<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $purchase_order_id
 * @property-read PurchaseOrder|null $purchaseOrder
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
