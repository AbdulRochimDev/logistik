<?php

namespace App\Domain\Outbound\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $outbound_shipment_id
 * @property string $picklist_no
 * @property string $status
 * @property \Carbon\CarbonInterface|null $started_at
 * @property \Carbon\CarbonInterface|null $completed_at
 * @property int|null $picker_id
 * @property-read OutboundShipment|null $outboundShipment
 * @property-read User|null $picker
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PickLine> $lines
 * @property-read int|null $lines_count
 *
 * @method static \Database\Factories\PickListFactory newFactory()
 */
class PickList extends Model
{
    use HasFactory;

    protected $table = 'pick_lists';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function outboundShipment(): BelongsTo
    {
        return $this->belongsTo(OutboundShipment::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picker_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PickLine::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PickListFactory::new();
    }
}
