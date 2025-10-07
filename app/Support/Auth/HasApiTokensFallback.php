<?php

namespace App\Support\Auth;

use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Lightweight replacement for the Sanctum HasApiTokens trait when the package is not installed.
 */
trait HasApiTokensFallback
{
    /**
     * @return Collection<int, mixed>
     */
    public function tokens(): Collection
    {
        return new Collection();
    }

    public function currentAccessToken(): mixed
    {
        return null;
    }

    public function withAccessToken(mixed $token): static
    {
        return $this;
    }

    public function tokenCan(string $ability): bool
    {
        return true;
    }

    /**
     * @param  array<int|string, mixed>  $abilities
     */
    public function createToken(string $name, array $abilities = ['*'], $expiresAt = null): never
    {
        throw new RuntimeException('Laravel Sanctum is not installed. Install laravel/sanctum to issue API tokens.');
    }
}
