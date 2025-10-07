<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('wms.auth.admin_password', 'ChangeMe!123');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Gudang',
                'password' => Hash::make($password),
            ]
        );

        $role = Role::query()->firstOrCreate(
            ['name' => 'admin_gudang'],
            ['description' => 'Warehouse administrator']
        );

        $admin->roles()->syncWithoutDetaching([$role->id]);
    }
}
