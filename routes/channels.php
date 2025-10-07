<?php

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('wms.*', function (User $user) {
    if (! $user->hasAnyRole(['admin_gudang', 'driver'])) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

Broadcast::channel('wms.outbound.shipment.{shipmentId}', function (User $user, int $shipmentId) {
    if ($user->hasRole('admin_gudang')) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    $shipment = Shipment::query()->find($shipmentId);

    if (! $shipment) {
        return false;
    }

    if ($user->hasRole('driver') && Gate::forUser($user)->allows('driver-access-shipment', $shipment)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});
