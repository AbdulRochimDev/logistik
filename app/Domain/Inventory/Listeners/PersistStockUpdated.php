<?php

namespace App\Domain\Inventory\Listeners;

use App\Domain\Inventory\Events\StockUpdated;
use App\Domain\Inventory\Models\StockMovementAudit;
use Carbon\CarbonImmutable;

class PersistStockUpdated
{
    public function __invoke(StockUpdated $event): void
    {
        $payload = $event->payload;

        StockMovementAudit::query()->updateOrCreate(
            ['movement_id' => $payload['movement_id']],
            [
                'context' => $event->context,
                'type' => $payload['type'],
                'ref_type' => $payload['ref_type'],
                'ref_id' => $payload['ref_id'],
                'warehouse_code' => $payload['warehouse_code'],
                'location_code' => $payload['location_code'] ?? null,
                'sku' => $payload['sku'] ?? null,
                'qty_on_hand' => $payload['qty_on_hand'],
                'qty_allocated' => $payload['qty_allocated'],
                'quantity' => $payload['quantity'],
                'moved_at' => isset($payload['moved_at']) && $payload['moved_at']
                    ? CarbonImmutable::parse($payload['moved_at'])
                    : CarbonImmutable::now(),
            ]
        );
    }
}
