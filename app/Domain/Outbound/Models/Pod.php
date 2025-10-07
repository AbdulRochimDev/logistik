<?php

namespace App\Domain\Outbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shipment_id
 * @property string $signed_by
 * @property string|null $signer_id
 * @property \Carbon\CarbonInterface $signed_at
 * @property array|null $meta
 * @property string|null $external_idempotency_key
 * @property-read Shipment $shipment
 *
 * @method static \Database\Factories\PodFactory newFactory()
 */
class Pod extends Model
{
    use HasFactory;

    protected $table = 'pods';

    protected $guarded = [];

    protected $casts = [
        'signed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PodFactory::new();
    }
}
