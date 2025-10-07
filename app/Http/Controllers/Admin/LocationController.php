<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LocationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $query = Location::query()
            ->with('warehouse')
            ->when($keyword !== '', function (Builder $builder) use ($keyword): void {
                $builder->where(function (Builder $builder) use ($keyword): void {
                    $builder->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('warehouse_id'), function (Builder $builder) use ($request): void {
                $builder->where('warehouse_id', (int) $request->input('warehouse_id'));
            })
            ->orderBy('warehouse_id')
            ->orderBy('code');

        $locations = $query->paginate(15)->withQueryString();
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('admin.locations.index', compact('locations', 'warehouses'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('admin.locations.create', compact('warehouses'));
    }

    public function store(LocationRequest $request): RedirectResponse
    {
        Location::query()->create($request->validated());

        return redirect()
            ->route('admin.locations.index')
            ->with('status', 'Lokasi berhasil dibuat.');
    }

    public function edit(Location $location): View
    {
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('admin.locations.edit', compact('location', 'warehouses'));
    }

    public function update(LocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        return redirect()
            ->route('admin.locations.index')
            ->with('status', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $hasStock = Stock::query()
            ->where('location_id', $location->id)
            ->where(function (Builder $query): void {
                $query->where('qty_on_hand', '>', 0)
                    ->orWhere('qty_allocated', '>', 0);
            })
            ->exists();

        if ($hasStock) {
            return redirect()
                ->route('admin.locations.index')
                ->withErrors(['delete' => 'Lokasi tidak dapat dihapus karena masih memiliki stok.']);
        }

        $location->delete();

        return redirect()
            ->route('admin.locations.index')
            ->with('status', 'Lokasi berhasil dihapus.');
    }
}
