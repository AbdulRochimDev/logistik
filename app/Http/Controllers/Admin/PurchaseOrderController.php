<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inbound\Models\Supplier;
use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrderRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use RuntimeException;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function index(Request $request): View
    {
        $statuses = ['draft', 'approved', 'closed'];

        $search = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where('po_no', 'like', "%{$search}%");
            })
            ->when($statusFilter !== '', function (Builder $builder) use ($statusFilter, $statuses): void {
                if (in_array($statusFilter, $statuses, true)) {
                    $builder->where('status', $statusFilter);
                }
            })
            ->when($request->filled('supplier_id'), function (Builder $builder) use ($request): void {
                $builder->where('supplier_id', (int) $request->input('supplier_id'));
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::query()->orderBy('name')->get();
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('admin.purchase_orders.index', compact('purchaseOrders', 'suppliers', 'warehouses', 'statuses'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()->orderBy('name')->get();
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $items = Item::query()->orderBy('sku')->get();

        return view('admin.purchase_orders.create', compact('suppliers', 'warehouses', 'items'));
    }

    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $userId = (int) $request->user()->id;

        $purchaseOrder = $this->db->transaction(function () use ($validated, $userId) {
            $po = PurchaseOrder::query()->create([
                'po_no' => $validated['po_no'],
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'status' => $validated['status'],
                'eta' => $validated['eta'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $userId,
                'approved_by' => $validated['status'] === 'approved' ? $userId : null,
            ]);

            $lines = collect($validated['lines'])
                ->map(fn (array $line) => [
                    'item_id' => $line['item_id'],
                    'uom' => $line['uom'],
                    'ordered_qty' => $line['qty_ordered'],
                    'received_qty' => 0,
                ])->all();

            $po->items()->createMany($lines);

            return $po;
        });

        return redirect()
            ->route('admin.purchase-orders.edit', $purchaseOrder)
            ->with('status', 'Purchase order berhasil dibuat.');
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['items.item']);
        $suppliers = Supplier::query()->orderBy('name')->get();
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $items = Item::query()->orderBy('sku')->get();

        return view('admin.purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'warehouses', 'items'));
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validated();
        $userId = (int) $request->user()->id;

        try {
            $this->db->transaction(function () use ($purchaseOrder, $validated, $userId): void {
                $purchaseOrder->fill([
                    'po_no' => $validated['po_no'],
                    'supplier_id' => $validated['supplier_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'status' => $validated['status'],
                    'eta' => $validated['eta'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($validated['status'] === 'approved' && ! $purchaseOrder->approved_by) {
                    $purchaseOrder->approved_by = $userId;
                }

                if ($validated['status'] !== 'approved') {
                    $purchaseOrder->approved_by = null;
                }

                $purchaseOrder->save();

                /** @var \Illuminate\Database\Eloquent\Collection<int, PoItem> $existingLines */
                $existingLines = $purchaseOrder->items()->lockForUpdate()->get()->keyBy('id');
                $keptIds = [];

                foreach ($validated['lines'] as $lineData) {
                    $lineId = Arr::get($lineData, 'id');
                    $lineId = $lineId !== null ? (int) $lineId : null;

                    if ($lineId !== null && $existingLines->has($lineId)) {
                        /** @var PoItem|null $poItem */
                        $poItem = $existingLines->get($lineId);
                        if ($poItem === null) {
                            continue;
                        }

                        if ($poItem->received_qty > $lineData['qty_ordered'] + 0.0001) {
                            throw new RuntimeException('Ordered quantity lebih kecil dari qty yang sudah diterima.');
                        }

                        $poItem->update([
                            'item_id' => $lineData['item_id'],
                            'uom' => $lineData['uom'],
                            'ordered_qty' => $lineData['qty_ordered'],
                        ]);

                        $keptIds[] = $poItem->id;
                    } else {
                        /** @var PoItem $created */
                        $created = $purchaseOrder->items()->create([
                            'item_id' => $lineData['item_id'],
                            'uom' => $lineData['uom'],
                            'ordered_qty' => $lineData['qty_ordered'],
                            'received_qty' => 0,
                        ]);

                        $keptIds[] = $created->id;
                    }
                }

                foreach ($purchaseOrder->items()->whereNotIn('id', $keptIds)->get() as $poItem) {
                    /** @var PoItem $poItem */
                    if ($poItem->received_qty > 0) {
                        throw new RuntimeException('Tidak bisa menghapus line PO yang sudah menerima barang.');
                    }

                    $poItem->delete();
                }
            });
        } catch (RuntimeException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['lines' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.purchase-orders.edit', $purchaseOrder)
            ->with('status', 'Purchase order berhasil diperbarui.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->items()->where('received_qty', '>', 0)->exists()
            || $purchaseOrder->inboundShipment()->exists()
            || InboundShipment::query()->where('purchase_order_id', $purchaseOrder->id)->whereHas('grns')->exists()) {
            return redirect()
                ->route('admin.purchase-orders.index')
                ->withErrors(['delete' => 'Purchase order memiliki inbound/grn dan tidak dapat dihapus.']);
        }

        $this->db->transaction(function () use ($purchaseOrder): void {
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();
        });

        return redirect()
            ->route('admin.purchase-orders.index')
            ->with('status', 'Purchase order berhasil dihapus.');
    }
}
