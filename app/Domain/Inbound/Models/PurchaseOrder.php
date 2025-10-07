<?php

namespace App\Domain\Inbound\Models;

use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $po_no
 * @property string $status
 * @property \Carbon\CarbonInterface|null $eta
 * @property string|null $notes
 * @property int $created_by
 * @property int|null $approved_by
 * @property-read Supplier|null $supplier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PoItem> $items
 * @property-read int|null $items_count
 * @property-read InboundShipment|null $inboundShipment
 * @property-read Warehouse|null $warehouse
 *
 * @method static \Database\Factories\PurchaseOrderFactory newFactory()
 */
class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $guarded = [];

    protected $casts = [
        'eta' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PoItem::class);
    }

    public function inboundShipment(): HasOne
    {
        return $this->hasOne(InboundShipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PurchaseOrderFactory::new();
    }
}
