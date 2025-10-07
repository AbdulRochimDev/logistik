<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Customer;
use App\Domain\Outbound\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'so_no' => strtoupper($this->faker->unique()->bothify('SO-####')),
            'status' => 'approved',
            'ship_by' => now()->addDays(3),
            'notes' => $this->faker->sentence(),
            'created_by' => User::factory(),
            'approved_by' => User::factory(),
        ];
    }
}
