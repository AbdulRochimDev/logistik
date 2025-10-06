<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoItem extends Model
{
    use HasFactory;

    protected $table = 'po_items';

    protected $guarded = [];

    protected $casts = [
        'ordered_qty' => 'float',
        'received_qty' => 'float',
    ];
}
