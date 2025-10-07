<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('wms.*', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
