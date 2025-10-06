<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnLine extends Model
{
    use HasFactory;

    protected $table = 'grn_lines';

    protected $guarded = [];

    protected $casts = [
        'received_qty' => 'float',
        'rejected_qty' => 'float',
    ];
}
