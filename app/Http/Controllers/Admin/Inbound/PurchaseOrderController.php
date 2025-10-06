<?php

namespace App\Http\Controllers\Admin\Inbound;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inbound\PurchaseOrderStoreRequest;
use App\Http\Requests\Admin\Inbound\PurchaseOrderUpdateRequest;
use App\Http\Resources\Admin\Inbound\PurchaseOrderCollection;
use App\Http\Resources\Admin\Inbound\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): PurchaseOrderCollection
    {
        $purchaseOrders = PurchaseOrder::all();

        return new PurchaseOrderCollection($purchaseOrders);
    }

    public function store(PurchaseOrderStoreRequest $request): Response
    {
        
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($purchaseOrder);
    }

    public function update(PurchaseOrderUpdateRequest $request, PurchaseOrder $purchaseOrder): Response
    {
        
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        {{ body }}
    }
}
