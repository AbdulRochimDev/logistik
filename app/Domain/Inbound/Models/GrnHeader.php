<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnHeader extends Model
{
    use HasFactory;

    protected $table = 'grn_headers';

    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
    ];
}
