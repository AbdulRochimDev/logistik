<?php

use App\Models\User;

it('provides a graceful fallback when Sanctum is unavailable', function (): void {
    $user = User::factory()->make();

    expect($user->currentAccessToken())->toBeNull();
    expect($user->tokenCan('any-ability'))->toBeTrue();
    expect($user->withAccessToken(null))->toBeInstanceOf(User::class);
    expect(fn () => $user->createToken('demo'))->toThrow(\RuntimeException::class);
});
