<?php

namespace App\Support\Auth;

if (trait_exists('Laravel\\Sanctum\\HasApiTokens')) {
    trait InteractsWithApiTokens
    {
        use \Laravel\Sanctum\HasApiTokens;
    }
} else {
    trait InteractsWithApiTokens
    {
        use HasApiTokensFallback;
    }
}
