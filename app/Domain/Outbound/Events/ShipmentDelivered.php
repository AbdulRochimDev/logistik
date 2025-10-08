<?php

namespace App\Domain\Outbound\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * @phpstan-type ShipmentDeliveredPayload array{
 *     shipment_id:int,
 *     pod_id:int|null,
 *     delivered_at:string|null,
 *     signer:string|null
 * }
 */
final class ShipmentDelivered implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  ShipmentDeliveredPayload  $payload
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
        return 'shipment.delivered';
    }

    /**
     * @return ShipmentDeliveredPayload
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
