<?php

namespace App\Models;

use App\Domain\Auth\Models\Role;
use App\Domain\Outbound\Models\Driver;
use App\Support\Auth\InteractsWithApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read \Illuminate\Support\Collection<int, Role> $roles
 * @property-read Driver|null $driver
 *
 * @method static \Database\Factories\UserFactory newFactory()
 */
class User extends Authenticatable
{
    use InteractsWithApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains(fn (Role $model) => $model->name === $role)
            || $this->roles()->where('name', $role)->exists();
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $roles = array_unique($roles);

        return $this->roles->contains(fn (Role $model) => in_array($model->name, $roles, true))
            || $this->roles()->whereIn('name', $roles)->exists();
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }
}
