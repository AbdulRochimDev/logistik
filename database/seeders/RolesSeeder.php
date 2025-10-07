<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'admin_gudang'],
            ['description' => 'Warehouse administrator']
        );

        Role::updateOrCreate(
            ['name' => 'driver'],
            ['description' => 'Delivery driver']
        );
    }
}
