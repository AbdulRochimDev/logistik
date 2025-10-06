<?php

namespace Tests\Feature\Http\Controllers\Driver;

use App\Models\Assignment;
use App\Models\DriverProfile;
use App\Models\OutboundShipment;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Driver\AssignmentController
 */
final class AssignmentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $assignments = Assignment::factory()->count(3)->create();

        $response = $this->get(route('assignments.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Driver\AssignmentController::class,
            'store',
            \App\Http\Requests\Driver\AssignmentControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $assignment_no = fake()->word();
        $driver_profile = DriverProfile::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $outbound_shipment = OutboundShipment::factory()->create();
        $assigned_at = Carbon::parse(fake()->dateTime());
        $status = fake()->randomElement(/** enum_attributes **/);

        $response = $this->post(route('assignments.store'), [
            'assignment_no' => $assignment_no,
            'driver_profile_id' => $driver_profile->id,
            'vehicle_id' => $vehicle->id,
            'outbound_shipment_id' => $outbound_shipment->id,
            'assigned_at' => $assigned_at->toDateTimeString(),
            'status' => $status,
        ]);

        $assignments = Assignment::query()
            ->where('assignment_no', $assignment_no)
            ->where('driver_profile_id', $driver_profile->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('outbound_shipment_id', $outbound_shipment->id)
            ->where('assigned_at', $assigned_at)
            ->where('status', $status)
            ->get();
        $this->assertCount(1, $assignments);
        $assignment = $assignments->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $assignment = Assignment::factory()->create();

        $response = $this->get(route('assignments.show', $assignment));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Driver\AssignmentController::class,
            'update',
            \App\Http\Requests\Driver\AssignmentControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $assignment = Assignment::factory()->create();
        $status = fake()->randomElement(/** enum_attributes **/);
        $completed_at = Carbon::parse(fake()->dateTime());

        $response = $this->put(route('assignments.update', $assignment), [
            'status' => $status,
            'completed_at' => $completed_at,
        ]);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $assignment = Assignment::factory()->create();

        $response = $this->delete(route('assignments.destroy', $assignment));

        $response->assertNoContent();

        $this->assertModelMissing($assignment);
    }
}
