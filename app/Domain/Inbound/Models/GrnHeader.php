<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrnHeader extends Model
{
    use HasFactory;

    protected $table = 'grn_headers';

    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function inboundShipment(): BelongsTo
    {
        return $this->belongsTo(InboundShipment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'grn_header_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\GrnHeaderFactory::new();
    }
}
