<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $guarded = [];

    protected $casts = [
        'eta' => 'date',
    ];
}
