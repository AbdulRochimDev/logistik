<?php

namespace Laravel\Sanctum;

if (! trait_exists(HasApiTokens::class)) {
    trait HasApiTokens
    {
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

        public function createToken(string $name, array $abilities = ['*'], $expiresAt = null): mixed
        {
            throw new \RuntimeException('Laravel Sanctum is not installed. Install laravel/sanctum to issue API tokens.');
        }
    }
}
