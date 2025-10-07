<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\Vehicle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Requests\Admin\UpdateVehicleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->withCount(['shipments' => fn ($query) => $query->whereIn('status', ['allocated', 'dispatched'])])
            ->when($request->string('status')->trim(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('plate_no')
            ->paginate(15)
            ->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('admin.vehicles.create');
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::query()->create($request->validated());

        return redirect()->route('admin.vehicles.index')->with('status', 'Kendaraan berhasil dibuat.');
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('admin.vehicles.index')->with('status', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $hasActiveShipment = Shipment::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', '!=', 'delivered')
            ->exists();

        if ($hasActiveShipment) {
            return redirect()->route('admin.vehicles.index')
                ->withErrors(['delete' => 'Kendaraan masih dipakai oleh shipment aktif.']);
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('status', 'Kendaraan berhasil dihapus.');
    }
}
