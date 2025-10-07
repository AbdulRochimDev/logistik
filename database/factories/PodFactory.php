<?php

namespace Database\Factories;

use App\Domain\Outbound\Models\Pod;
use App\Domain\Outbound\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pod>
 */
class PodFactory extends Factory
{
    protected $model = Pod::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'signed_by' => $this->faker->name(),
            'signer_id' => $this->faker->numerify('ID####'),
            'signed_at' => now(),
            'photo_path' => null,
            'signature_path' => null,
            'notes' => $this->faker->sentence(),
            'meta' => ['note' => $this->faker->words(3, true)],
            'external_idempotency_key' => strtoupper($this->faker->unique()->bothify('PODKEY####')),
        ];
    }
}
