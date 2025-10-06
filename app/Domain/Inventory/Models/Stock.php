<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stocks';

    protected $fillable = [
        'warehouse_id',
        'location_id',
        'item_id',
        'item_lot_id',
        'qty_on_hand',
        'qty_allocated',
    ];

    protected $casts = [
        'warehouse_id' => 'int',
        'location_id' => 'int',
        'item_id' => 'int',
        'item_lot_id' => 'int',
        'qty_on_hand' => 'float',
        'qty_allocated' => 'float',
        'qty_available' => 'float',
    ];
}
