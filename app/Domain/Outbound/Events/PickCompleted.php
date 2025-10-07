<?php

namespace App\Domain\Outbound\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * @phpstan-type PickCompletedPayload array{
 *     shipment_id:int,
 *     shipment_item_id:int,
 *     item_id:int,
 *     lot_id?:int|null,
 *     qty_picked:float,
 *     picked_at:string|null,
 *     actor_user_id?:int|null
 * }
 */
final class PickCompleted implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  PickCompletedPayload  $payload
     */
    public function __construct(public readonly array $payload)
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('wms.outbound.shipment.' . $this->payload['shipment_id'])];
    }

    public function broadcastAs(): string
    {
        return 'pick.completed';
    }

    /**
     * @return PickCompletedPayload
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
