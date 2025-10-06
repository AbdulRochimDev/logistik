<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundShipment extends Model
{
    use HasFactory;

    protected $table = 'inbound_shipments';

    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}
