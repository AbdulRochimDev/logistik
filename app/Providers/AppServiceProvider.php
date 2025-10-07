<?php

namespace App\Providers;

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('driver-access-shipment', function (User $user, Shipment $shipment): bool {
            $driver = $user->driver;

            if (! $driver) {
                return false;
            }

            if ((int) $shipment->driver_id === $driver->id) {
                return true;
            }

            return $shipment->assignments()->where('driver_id', $driver->id)->exists();
        });
    }
}
