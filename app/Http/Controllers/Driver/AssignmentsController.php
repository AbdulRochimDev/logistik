<?php

namespace App\Http\Controllers\Driver;

use App\Domain\Outbound\Models\Shipment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class AssignmentsController
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();
        $driver = $user?->driver;

        if (! $driver) {
            abort(403, 'Driver profile not found.');
        }

        $shipments = Shipment::query()
            ->with([
                'items.item',
                'items.salesOrderItem',
                'driver',
                'vehicle',
                'warehouse',
                'outboundShipment.salesOrder.customer',
            ])
            ->whereIn('status', ['allocated', 'dispatched'])
            ->where(function ($query) use ($driver): void {
                $query->where('driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
            ->orderBy('planned_at')
            ->get();

        return response()->json([
            'data' => $shipments->map(function (Shipment $shipment) {
                $totalPlanned = $shipment->items->sum('qty_planned');
                $totalDelivered = $shipment->items->sum('qty_delivered');
                $totalPicked = $shipment->items->sum('qty_picked');
                $firstItem = $shipment->items->first();
                $uom = $firstItem?->salesOrderItem?->uom ?? $firstItem?->item?->default_uom ?? 'PCS';

                $salesOrder = $shipment->outboundShipment?->salesOrder;
                $customer = $salesOrder?->customer;
                $warehouse = $shipment->warehouse;

                $etaMinutes = null;
                if ($shipment->planned_at) {
                    $etaMinutes = max(0, Carbon::now()->diffInMinutes($shipment->planned_at, false));
                }

                $progress = $totalPlanned > 0
                    ? [
                        'picked_ratio' => round($totalPicked / $totalPlanned, 2),
                        'delivered_ratio' => round($totalDelivered / $totalPlanned, 2),
                    ]
                    : [
                        'picked_ratio' => 0.0,
                        'delivered_ratio' => 0.0,
                    ];

                return [
                    'shipment' => $shipment,
                    'status_snapshot' => [
                        'status' => $shipment->status,
                        'status_badge' => Str::headline($shipment->status),
                        'next_action' => $this->determineNextAction($shipment->status, $progress['delivered_ratio']),
                        'progress' => $progress,
                        'timestamps' => [
                            'planned_at' => optional($shipment->planned_at)->toISOString(),
                            'dispatched_at' => optional($shipment->dispatched_at)->toISOString(),
                            'delivered_at' => optional($shipment->delivered_at)->toISOString(),
                        ],
                    ],
                    'navigation' => [
                        'origin' => [
                            'name' => $warehouse?->name,
                            'address' => $warehouse?->address,
                        ],
                        'destination' => [
                            'name' => $customer?->name,
                            'address' => $customer?->address,
                            'contact' => $customer?->phone,
                        ],
                        'eta_minutes' => $etaMinutes,
                    ],
                    'load_summary' => [
                        'total_qty' => round($totalPlanned, 3),
                        'uom' => $uom,
                        'unique_skus' => $shipment->items->unique('item_id')->count(),
                        'vehicle_capacity' => $shipment->vehicle?->capacity,
                    ],
                ];
            }),
        ]);
    }

    private function determineNextAction(string $status, float $deliveredRatio): string
    {
        return match ($status) {
            'allocated' => 'Mulai pengambilan & dispatch',
            'dispatched' => $deliveredRatio >= 1.0 ? 'Konfirmasi POD' : 'Selesaikan pengantaran',
            'delivered' => 'Selesai',
            default => 'Review status',
        };
    }
}
