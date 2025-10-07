<?php

namespace App\Http\Controllers\Driver;

use App\Domain\Outbound\Models\Shipment;
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
            ->with(['items', 'driver', 'vehicle'])
            ->whereIn('status', ['allocated', 'dispatched'])
            ->where(function ($query) use ($driver): void {
                $query->where('driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
            ->orderBy('planned_at')
            ->get();

        return response()->json([
            'data' => $shipments,
        ]);
    }
}
