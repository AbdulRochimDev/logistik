<?php

namespace App\Domain\Outbound\Models;

use App\Domain\Inventory\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $customer_id
 * @property string $so_no
 * @property-read Warehouse $warehouse
 * @property-read Customer $customer
 */
class SalesOrder extends Model
{
    use HasFactory;

    protected $table = 'sales_orders';

    protected $guarded = [];

    protected $casts = [
        'ship_by' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SoItem::class);
    }

    public function outboundShipment(): HasOne
    {
        return $this->hasOne(OutboundShipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\SalesOrderFactory::new();
    }
}
