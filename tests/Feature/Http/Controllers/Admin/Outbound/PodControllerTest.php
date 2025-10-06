<?php

namespace Tests\Feature\Http\Controllers\Admin\Outbound;

use App\Models\Shipment;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Outbound\PodController
 */
final class PodControllerTest extends TestCase
{
    use AdditionalAssertions, WithFaker;

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Outbound\PodController::class,
            'store',
            \App\Http\Requests\Admin\Outbound\PodControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_behaves_as_expected(): void
    {
        $shipment = Shipment::factory()->create();
        $signed_at = Carbon::parse(fake()->dateTime());
        $signed_by = fake()->word();

        $response = $this->post(route('pods.store'), [
            'shipment_id' => $shipment->id,
            'signed_at' => $signed_at,
            'signed_by' => $signed_by,
        ]);
    }
}
