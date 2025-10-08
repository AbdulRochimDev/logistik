<?php

namespace App\Support\Idempotency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $context
 * @property string $key
 * @property string $request_hash
 * @property \Carbon\CarbonInterface|null $last_used_at
 */
class IdempotencyKey extends Model
{
    use HasFactory;

    protected $table = 'idempotency_keys';

    protected $guarded = [];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];
}
