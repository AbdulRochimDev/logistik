<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Models\StockMovementAudit;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class StockMonitorController
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function __invoke(): View
    {
        $matrix = collect($this->stockService->movementMatrix())
            ->map(fn (array $rules, string $type) => [
                'type' => Str::headline($type),
                'raw_type' => $type,
                'rules' => collect($rules)->map(function (array $rule) {
                    return [
                        'direction' => $rule['direction'],
                        'on_hand' => $rule['on_hand'],
                        'allocated' => $rule['allocated'],
                    ];
                }),
            ]);

        $recentAudits = StockMovementAudit::query()
            ->orderByDesc('moved_at')
            ->limit(20)
            ->get();

        $typeSummary = StockMovementAudit::query()
            ->selectRaw('type, COUNT(*) as count, SUM(quantity) as total_qty')
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'type' => Str::headline($row->type),
                'raw_type' => $row->type,
                'count' => (int) $row->count,
                'total_qty' => (float) $row->total_qty,
            ]);

        return view('admin.stock-monitor.index', [
            'matrix' => $matrix,
            'recentAudits' => $recentAudits,
            'typeSummary' => $typeSummary,
        ]);
    }
}
