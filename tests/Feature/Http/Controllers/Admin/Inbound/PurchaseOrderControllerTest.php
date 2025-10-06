<?php

namespace Tests\Feature\Http\Controllers\Admin\Inbound;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Inbound\PurchaseOrderController
 */
final class PurchaseOrderControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $purchaseOrders = PurchaseOrder::factory()->count(3)->create();

        $response = $this->get(route('purchase-orders.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Inbound\PurchaseOrderController::class,
            'store',
            \App\Http\Requests\Admin\Inbound\PurchaseOrderControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_behaves_as_expected(): void
    {
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $po_no = fake()->word();
        $eta = Carbon::parse(fake()->date());
        $notes = fake()->text();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'po_no' => $po_no,
            'eta' => $eta,
            'notes' => $notes,
        ]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create();

        $response = $this->get(route('purchase-orders.show', $purchaseOrder));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Inbound\PurchaseOrderController::class,
            'update',
            \App\Http\Requests\Admin\Inbound\PurchaseOrderControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $status = fake()->randomElement(/** enum_attributes **/);
        $eta = Carbon::parse(fake()->date());
        $notes = fake()->text();

        $response = $this->put(route('purchase-orders.update', $purchaseOrder), [
            'status' => $status,
            'eta' => $eta,
            'notes' => $notes,
        ]);
    }


    #[Test]
    public function destroy_behaves_as_expected(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create();

        $response = $this->delete(route('purchase-orders.destroy', $purchaseOrder));
    }
}
