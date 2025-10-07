<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Inbound\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\Models\Shipment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardController
{
    public function __invoke(): View
    {
        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();

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

        $movementSums = StockMovement::query()
            ->select('warehouse_id')
            ->selectRaw("SUM(CASE WHEN type = 'pick' THEN quantity ELSE 0 END) as picked_qty")
            ->selectRaw("SUM(CASE WHEN type = 'deliver' THEN quantity ELSE 0 END) as delivered_qty")
            ->whereBetween('moved_at', [$startOfDay, $endOfDay])
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        $warehouseBreakdown->transform(function (Warehouse $warehouse) use ($movementSums) {
            $totals = $movementSums->get($warehouse->id);

            $warehouse->setAttribute('picked_today', $totals ? (float) $totals->picked_qty : 0.0);
            $warehouse->setAttribute('delivered_today', $totals ? (float) $totals->delivered_qty : 0.0);

            return $warehouse;
        });

        $openPurchaseOrders = PurchaseOrder::query()
            ->whereNotIn('status', ['completed', 'closed'])
            ->count();

        $openShipmentIds = Shipment::query()
            ->where('status', '!=', 'delivered')
            ->pluck('id');

        $pickedToday = (float) StockMovement::query()
            ->where('type', 'pick')
            ->whereBetween('moved_at', [$startOfDay, $endOfDay])
            ->sum('quantity');

        $deliveredToday = (float) StockMovement::query()
            ->where('type', 'deliver')
            ->whereBetween('moved_at', [$startOfDay, $endOfDay])
            ->sum('quantity');

        $openShipments = Shipment::query()
            ->where('status', '!=', 'delivered')
            ->count();

        $movementEntries = StockMovement::query()
            ->with(['item', 'warehouse'])
            ->orderByDesc(DB::raw('COALESCE(moved_at, created_at)'))
            ->limit(6)
            ->get()
            ->map(function (StockMovement $movement) {
                $timestamp = $movement->moved_at ?? $movement->created_at;
                $item = $movement->item;
                $warehouse = $movement->warehouse;
                $metaSegments = array_filter([
                    $item ? 'SKU: ' . $item->sku : null,
                    $warehouse ? $warehouse->name : null,
                ]);

                return [
                    'key' => 'movement-' . $movement->id,
                    'timestamp' => $timestamp,
                    'title' => Str::headline($movement->type ?? 'movement'),
                    'highlight' => number_format((float) $movement->quantity, 0, ',', '.'),
                    'meta' => implode(' · ', $metaSegments),
                    'remarks' => $movement->remarks,
                    'shipment_id' => null,
                ];
            });

        $dispatchEntries = Shipment::query()
            ->with(['driver'])
            ->whereNotNull('dispatched_at')
            ->orderByDesc('dispatched_at')
            ->limit(10)
            ->get()
            ->map(function (Shipment $shipment) {
                return [
                    'key' => 'dispatch-' . $shipment->id . '-' . optional($shipment->dispatched_at)->timestamp,
                    'timestamp' => $shipment->dispatched_at,
                    'title' => 'Shipment Dispatched · ' . ($shipment->shipment_no ?? ('Shipment #' . $shipment->id)),
                    'highlight' => 'Dispatched',
                    'meta' => $shipment->driver ? 'Driver: ' . $shipment->driver->name : null,
                    'remarks' => null,
                    'shipment_id' => $shipment->id,
                ];
            });

        $deliveryEntries = Shipment::query()
            ->with(['proofOfDelivery'])
            ->whereNotNull('delivered_at')
            ->orderByDesc('delivered_at')
            ->limit(10)
            ->get()
            ->map(function (Shipment $shipment) {
                $pod = $shipment->proofOfDelivery;

                return [
                    'key' => 'deliver-' . $shipment->id . '-' . optional($shipment->delivered_at)->timestamp,
                    'timestamp' => $shipment->delivered_at,
                    'title' => 'Shipment Delivered · ' . ($shipment->shipment_no ?? ('Shipment #' . $shipment->id)),
                    'highlight' => 'Delivered',
                    'meta' => $pod && $pod->signed_by ? 'Signer: ' . $pod->signed_by : null,
                    'remarks' => null,
                    'shipment_id' => $shipment->id,
                ];
            });

        $activityFeed = $movementEntries
            ->merge($dispatchEntries)
            ->merge($deliveryEntries)
            ->filter(fn (array $entry) => $entry['timestamp'] !== null)
            ->sortByDesc(fn (array $entry) => optional($entry['timestamp'])->timestamp ?? 0)
            ->take(20)
            ->values()
            ->map(function (array $entry) {
                $timestamp = $entry['timestamp'];

                $entry['timestamp_iso'] = $timestamp ? $timestamp->toIso8601String() : null;
                $entry['timestamp_human'] = $timestamp ? $timestamp->format('d M Y · H:i') : null;

                return $entry;
            });

        $relatedShipmentIds = $activityFeed
            ->pluck('shipment_id')
            ->filter()
            ->merge($openShipmentIds)
            ->unique()
            ->values();

        $shipmentMeta = $relatedShipmentIds->isEmpty()
            ? collect()
            : Shipment::query()
                ->whereIn('id', $relatedShipmentIds)
                ->get()
                ->mapWithKeys(function (Shipment $shipment) {
                    return [
                        $shipment->id => [
                            'label' => $shipment->shipment_no ?? ('Shipment #' . $shipment->id),
                            'reference' => $shipment->tracking_no,
                        ],
                    ];
                });

        return view('dashboard.admin', [
            'totals' => $totals,
            'openPurchaseOrders' => $openPurchaseOrders,
            'warehouseBreakdown' => $warehouseBreakdown,
            'activityFeed' => $activityFeed,
            'openShipments' => $openShipments,
            'pickedToday' => $pickedToday,
            'deliveredToday' => $deliveredToday,
            'openShipmentIds' => $openShipmentIds,
            'shipmentMeta' => $shipmentMeta->toArray(),
        ]);
    }
}
