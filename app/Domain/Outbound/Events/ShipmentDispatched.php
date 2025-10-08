<?php

namespace App\Domain\Outbound\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * @phpstan-type ShipmentDispatchedPayload array{
 *     shipment_id:int,
 *     driver_id:int|null,
 *     vehicle_id:int|null,
 *     dispatched_at:string|null
 * }
 */
final class ShipmentDispatched implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  ShipmentDispatchedPayload  $payload
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
        return 'shipment.dispatched';
    }

    /**
     * @return ShipmentDispatchedPayload
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
