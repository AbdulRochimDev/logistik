<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $movement_id
 * @property string $context
 * @property string $type
 * @property string $warehouse_code
 * @property string|null $location_code
 * @property string|null $sku
 * @property float $qty_on_hand
 * @property float $qty_allocated
 * @property float $quantity
 * @property \Carbon\CarbonInterface|null $moved_at
 */
class StockMovementAudit extends Model
{
    use HasFactory;

    protected $table = 'stock_movement_audits';

    protected $guarded = [];

    protected $casts = [
        'qty_on_hand' => 'float',
        'qty_allocated' => 'float',
        'quantity' => 'float',
        'moved_at' => 'datetime',
    ];
}
