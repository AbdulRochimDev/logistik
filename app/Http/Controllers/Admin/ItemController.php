<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inventory\Models\Item;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $lotTracked = $request->has('is_lot_tracked') ? $request->boolean('is_lot_tracked') : null;

        $query = Item::query()
            ->when($keyword !== '', function (Builder $builder) use ($keyword): void {
                $builder->where(function (Builder $builder) use ($keyword): void {
                    $builder->where('sku', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
            })
            ->when($lotTracked !== null, function (Builder $builder) use ($lotTracked): void {
                $builder->where('is_lot_tracked', $lotTracked);
            })
            ->orderBy('sku');

        $items = $query->paginate(15)->withQueryString();

        return view('admin.items.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.items.create');
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        Item::query()->create($request->validated());

        return redirect()
            ->route('admin.items.index')
            ->with('status', 'Item berhasil dibuat.');
    }

    public function edit(Item $item): View
    {
        return view('admin.items.edit', compact('item'));
    }

    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()
            ->route('admin.items.index')
            ->with('status', 'Item berhasil diperbarui.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        if (PoItem::query()->where('item_id', $item->id)->exists()) {
            return redirect()
                ->route('admin.items.index')
                ->withErrors(['delete' => 'Item terikat pada purchase order dan tidak dapat dihapus.']);
        }

        $item->delete();

        return redirect()
            ->route('admin.items.index')
            ->with('status', 'Item berhasil dihapus.');
    }
}
