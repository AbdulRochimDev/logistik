<?php

namespace App\Domain\Outbound\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $license_no
 * @property string $status
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DriverAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Shipment> $shipments
 * @property-read int|null $shipments_count
 *
 * @method static \Database\Factories\DriverFactory newFactory()
 */
class Driver extends Model
{
    use HasFactory;

    protected $table = 'drivers';

    protected $guarded = [];

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'driver_assignments')
            ->withTimestamps()
            ->withPivot('assigned_at');
    }

    protected static function newFactory()
    {
        return \Database\Factories\DriverFactory::new();
    }
}
