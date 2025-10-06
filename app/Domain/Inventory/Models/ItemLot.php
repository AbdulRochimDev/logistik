<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemLot extends Model
{
    use HasFactory;

    protected $table = 'item_lots';

    protected $guarded = [];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];
}
