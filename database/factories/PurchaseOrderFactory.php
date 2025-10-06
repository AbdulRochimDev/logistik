<?php

namespace Database\Factories;

use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inbound\Models\Supplier;
use App\Domain\Inventory\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'po_no' => $this->faker->unique()->numerify('PO#####'),
            'status' => 'draft',
            'eta' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'notes' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
