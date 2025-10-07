<?php

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * @phpstan-type StockPayload array{
 *     movement_id:int,
 *     type:string,
 *     ref_type:string,
 *     ref_id:string,
 *     warehouse_code:string,
 *     location_code:?string,
 *     sku:?string,
 *     qty_on_hand:float,
 *     qty_allocated:float,
 *     quantity:float,
 *     moved_at:?string
 * }
 */
class StockUpdated implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  StockPayload  $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $context,
        public readonly string $warehouseCode
    ) {}

    public static function fromMovement(StockMovement $movement): self
    {
        $movement->loadMissing([
            'stock.item',
            'stock.location.warehouse',
        ]);

        /** @var Stock|null $stock */
        $stock = $movement->stock;
        $item = $stock?->item;
        $location = $stock?->location;
        $warehouse = $location?->warehouse;

        $locationCode = $location ? $location->code : null;
        $sku = $item ? $item->sku : null;
        $qtyOnHand = $stock ? (float) $stock->qty_on_hand : 0.0;
        $qtyAllocated = $stock ? (float) $stock->qty_allocated : 0.0;

        $warehouseCode = strtoupper((string) ($warehouse->code ?? 'UNKNOWN'));

        $context = ($movement->ref_type === 'GRN' || $movement->type === 'inbound_putaway')
            ? 'inbound.grn'
            : 'scan';

        return new self(
            payload: [
                'movement_id' => $movement->id,
                'type' => $movement->type,
                'ref_type' => $movement->ref_type,
                'ref_id' => $movement->ref_id,
                'warehouse_code' => $warehouseCode,
                'location_code' => $locationCode,
                'sku' => $sku,
                'qty_on_hand' => $qtyOnHand,
                'qty_allocated' => $qtyAllocated,
                'quantity' => (float) $movement->quantity,
                'moved_at' => optional($movement->moved_at)->toISOString(),
            ],
            context: $context,
            warehouseCode: $warehouseCode,
        );
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('wms.'.$this->context.'.'.$this->warehouseCode)];
    }

    public function broadcastAs(): string
    {
        return 'StockUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
