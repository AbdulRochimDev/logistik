<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inbound\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->when($keyword !== '', function (Builder $builder) use ($keyword): void {
                $builder->where(function (Builder $builder) use ($keyword): void {
                    $builder->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('contact_name', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('admin.suppliers.create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Supplier::query()->create($request->validated());

        return redirect()
            ->route('admin.suppliers.index')
            ->with('status', 'Supplier berhasil dibuat.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('admin.suppliers.index')
            ->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return redirect()
                ->route('admin.suppliers.index')
                ->withErrors(['delete' => 'Supplier memiliki purchase order dan tidak dapat dihapus.']);
        }

        $supplier->delete();

        return redirect()
            ->route('admin.suppliers.index')
            ->with('status', 'Supplier berhasil dihapus.');
    }
}
