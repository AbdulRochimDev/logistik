<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardController
{
    public function __invoke(): View
    {
        $totals = Stock::query()
            ->selectRaw('COALESCE(SUM(qty_on_hand), 0) as qty_on_hand')
            ->selectRaw('COALESCE(SUM(qty_allocated), 0) as qty_allocated')
            ->selectRaw('COALESCE(SUM(qty_on_hand - qty_allocated), 0) as qty_available')
            ->first();

        $warehouseBreakdown = Warehouse::query()
            ->withCount('locations')
            ->withSum('stocks as qty_on_hand', 'qty_on_hand')
            ->withSum('stocks as qty_allocated', 'qty_allocated')
            ->orderBy('name')
            ->get();

        $openPurchaseOrders = PurchaseOrder::query()
            ->whereNotIn('status', ['completed', 'closed'])
            ->count();

        $recentMovements = StockMovement::query()
            ->with(['item', 'warehouse'])
            ->orderByDesc(DB::raw('COALESCE(moved_at, created_at)'))
            ->limit(6)
            ->get()
            ->map(function (StockMovement $movement) {
                $timestamp = $movement->moved_at ?? $movement->created_at;
                $item = $movement->item;
                $warehouse = $movement->warehouse;

                return [
                    'id' => $movement->id,
                    'type' => Str::headline($movement->type ?? 'movement'),
                    'quantity' => $movement->quantity,
                    'item' => $item ? $item->sku : 'SKU?',
                    'warehouse' => $warehouse ? $warehouse->name : null,
                    'timestamp' => $timestamp ? $timestamp->format('d M Y · H:i') : null,
                    'remarks' => $movement->remarks,
                ];
            });

        return view('dashboard.admin', [
            'totals' => $totals,
            'openPurchaseOrders' => $openPurchaseOrders,
            'warehouseBreakdown' => $warehouseBreakdown,
            'recentMovements' => $recentMovements,
        ]);
    }
}
