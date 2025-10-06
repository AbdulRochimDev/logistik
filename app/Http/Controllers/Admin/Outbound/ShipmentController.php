<?php

namespace App\Http\Controllers\Admin\Outbound;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Outbound\ShipmentStoreRequest;
use App\Http\Requests\Admin\Outbound\ShipmentUpdateRequest;
use App\Http\Resources\Admin\Outbound\ShipmentCollection;
use App\Http\Resources\Admin\Outbound\ShipmentResource;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShipmentController extends Controller
{
    public function index(Request $request): ShipmentCollection
    {
        $shipments = Shipment::all();

        return new ShipmentCollection($shipments);
    }

    public function store(ShipmentStoreRequest $request): Response
    {
        
    }

    public function show(Request $request, Shipment $shipment): ShipmentResource
    {
        return new ShipmentResource($shipment);
    }

    public function update(ShipmentUpdateRequest $request, Shipment $shipment): ShipmentResource
    {
        $shipment->update($request->validated());

        return new ShipmentResource($shipment);
    }

    public function destroy(Request $request, Shipment $shipment): Response
    {
        $shipment->delete();

        return response()->noContent();
    }
}
