<?php

namespace App\Http\Controllers\Admin\Outbound;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Outbound\SalesOrderStoreRequest;
use App\Http\Requests\Admin\Outbound\SalesOrderUpdateRequest;
use App\Http\Resources\Admin\Outbound\SalesOrderCollection;
use App\Http\Resources\Admin\Outbound\SalesOrderResource;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalesOrderController extends Controller
{
    public function index(Request $request): SalesOrderCollection
    {
        $salesOrders = SalesOrder::all();

        return new SalesOrderCollection($salesOrders);
    }

    public function store(SalesOrderStoreRequest $request): Response
    {
        
    }

    public function show(Request $request, SalesOrder $salesOrder): SalesOrderResource
    {
        return new SalesOrderResource($salesOrder);
    }

    public function update(SalesOrderUpdateRequest $request, SalesOrder $salesOrder): Response
    {
        
    }

    public function destroy(Request $request, SalesOrder $salesOrder): Response
    {
        $salesOrder->delete();

        return response()->noContent();
    }
}
