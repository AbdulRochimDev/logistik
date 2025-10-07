<?php

namespace App\Domain\Inbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $inbound_shipment_id
 * @property string $grn_no
 * @property \Carbon\CarbonInterface $received_at
 * @property string $status
 * @property int $received_by
 * @property int|null $verified_by
 * @property string|null $notes
 * @property-read InboundShipment $inboundShipment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GrnLine> $lines
 * @property-read int|null $lines_count
 *
 * @method static \Database\Factories\GrnHeaderFactory newFactory()
 */
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
