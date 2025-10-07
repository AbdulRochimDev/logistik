<?php

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $guarded = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'user_roles')
            ->withTimestamps();
    }
}
