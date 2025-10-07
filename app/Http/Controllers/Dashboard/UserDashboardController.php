<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserDashboardController
{
    public function __invoke(): View
    {
        $topStocks = Stock::query()
            ->with(['item', 'location'])
            ->orderByDesc('qty_on_hand')
            ->limit(5)
            ->get();

        $recentMovements = StockMovement::query()
            ->with('item')
            ->orderByDesc(DB::raw('COALESCE(moved_at, created_at)'))
            ->limit(5)
            ->get()
            ->map(function (StockMovement $movement) {
                $timestamp = $movement->moved_at ?? $movement->created_at;
                $item = $movement->item;

                return [
                    'id' => $movement->id,
                    'type' => Str::headline($movement->type ?? 'movement'),
                    'quantity' => $movement->quantity,
                    'item' => $item ? $item->sku : 'SKU?',
                    'timestamp' => $timestamp ? $timestamp->format('d M Y · H:i') : null,
                ];
            });

        return view('dashboard.user', [
            'topStocks' => $topStocks,
            'recentMovements' => $recentMovements,
        ]);
    }
}
