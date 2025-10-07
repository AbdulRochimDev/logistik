<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()
            ->whereIn('name', ['admin_gudang', 'driver'])
            ->get()
            ->keyBy('name');

        $adminPassword = config('wms.auth.admin_password', 'ChangeMe!123');
        $driverPassword = config('wms.auth.driver_password', 'password');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Gudang',
                'password' => Hash::make($adminPassword),
            ]
        );

        $driver = User::query()->updateOrCreate(
            ['email' => 'driver@example.com'],
            [
                'name' => 'Driver One',
                'password' => Hash::make($driverPassword),
            ]
        );

        $adminRole = Arr::get($roles, 'admin_gudang');
        if ($adminRole !== null) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $driverRole = Arr::get($roles, 'driver');
        if ($driverRole !== null) {
            $driver->roles()->syncWithoutDetaching([$driverRole->id]);
        }
    }
}
