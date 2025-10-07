<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\Shipment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDriverRequest;
use App\Http\Requests\Admin\UpdateDriverRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $drivers = Driver::query()
            ->withCount(['shipments' => fn ($query) => $query->whereIn('status', ['allocated', 'dispatched'])])
            ->when($request->string('status')->trim(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.drivers.index', [
            'drivers' => $drivers,
        ]);
    }

    public function create(): View
    {
        return view('admin.drivers.create');
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        Driver::query()->create($request->validated());

        return redirect()->route('admin.drivers.index')->with('status', 'Driver berhasil dibuat.');
    }

    public function edit(Driver $driver): View
    {
        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $driver->update($request->validated());

        return redirect()->route('admin.drivers.index')->with('status', 'Driver berhasil diperbarui.');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $hasActiveShipment = Shipment::query()
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'delivered')
            ->exists();

        if ($hasActiveShipment) {
            return redirect()->route('admin.drivers.index')
                ->withErrors(['delete' => 'Driver masih memiliki shipment aktif dan tidak dapat dihapus.']);
        }

        $driver->delete();

        return redirect()->route('admin.drivers.index')->with('status', 'Driver berhasil dihapus.');
    }
}
