<?php

namespace Tests\Feature\Http\Controllers\Admin\Outbound;

use App\Models\OutboundShipment;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Outbound\ShipmentController
 */
final class ShipmentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $shipments = Shipment::factory()->count(3)->create();

        $response = $this->get(route('shipments.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Outbound\ShipmentController::class,
            'store',
            \App\Http\Requests\Admin\Outbound\ShipmentControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_behaves_as_expected(): void
    {
        $outbound_shipment = OutboundShipment::factory()->create();
        $carrier = fake()->word();
        $tracking_no = fake()->word();

        $response = $this->post(route('shipments.store'), [
            'outbound_shipment_id' => $outbound_shipment->id,
            'carrier' => $carrier,
            'tracking_no' => $tracking_no,
        ]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $shipment = Shipment::factory()->create();

        $response = $this->get(route('shipments.show', $shipment));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Outbound\ShipmentController::class,
            'update',
            \App\Http\Requests\Admin\Outbound\ShipmentControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $shipment = Shipment::factory()->create();
        $outbound_shipment = OutboundShipment::factory()->create();

        $response = $this->put(route('shipments.update', $shipment), [
            'outbound_shipment_id' => $outbound_shipment->id,
        ]);

        $shipment->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($outbound_shipment->id, $shipment->outbound_shipment_id);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $shipment = Shipment::factory()->create();

        $response = $this->delete(route('shipments.destroy', $shipment));

        $response->assertNoContent();

        $this->assertModelMissing($shipment);
    }
}
