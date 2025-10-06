<?php

namespace Tests\Feature\Http\Controllers\Admin\Outbound;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Outbound\SalesOrderController
 */
final class SalesOrderControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $salesOrders = SalesOrder::factory()->count(3)->create();

        $response = $this->get(route('sales-orders.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Outbound\SalesOrderController::class,
            'store',
            \App\Http\Requests\Admin\Outbound\SalesOrderControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_behaves_as_expected(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $so_no = fake()->word();
        $ship_by = Carbon::parse(fake()->date());

        $response = $this->post(route('sales-orders.store'), [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'so_no' => $so_no,
            'ship_by' => $ship_by,
        ]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $salesOrder = SalesOrder::factory()->create();

        $response = $this->get(route('sales-orders.show', $salesOrder));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Outbound\SalesOrderController::class,
            'update',
            \App\Http\Requests\Admin\Outbound\SalesOrderControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $salesOrder = SalesOrder::factory()->create();
        $status = fake()->randomElement(/** enum_attributes **/);
        $ship_by = Carbon::parse(fake()->date());
        $notes = fake()->text();

        $response = $this->put(route('sales-orders.update', $salesOrder), [
            'status' => $status,
            'ship_by' => $ship_by,
            'notes' => $notes,
        ]);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $salesOrder = SalesOrder::factory()->create();

        $response = $this->delete(route('sales-orders.destroy', $salesOrder));

        $response->assertNoContent();

        $this->assertModelMissing($salesOrder);
    }
}
