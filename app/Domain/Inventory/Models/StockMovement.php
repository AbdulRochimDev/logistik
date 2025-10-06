<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'stock_id',
        'type',
        'warehouse_id',
        'item_id',
        'item_lot_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'uom',
        'ref_type',
        'ref_id',
        'actor_user_id',
        'remarks',
        'moved_at',
    ];

    protected $casts = [
        'stock_id' => 'int',
        'warehouse_id' => 'int',
        'item_id' => 'int',
        'item_lot_id' => 'int',
        'from_location_id' => 'int',
        'to_location_id' => 'int',
        'quantity' => 'float',
        'moved_at' => 'datetime',
    ];
}
