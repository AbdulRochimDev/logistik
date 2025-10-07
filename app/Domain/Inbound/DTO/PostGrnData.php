<?php

namespace App\Domain\Inbound\DTO;

use Carbon\CarbonImmutable;

final class PostGrnData
{
    /**
     * @param  PostGrnLineData[]  $lines
     */
    public function __construct(
        public readonly ?int $grnHeaderId,
        public readonly int $inboundShipmentId,
        public readonly CarbonImmutable $receivedAt,
        public readonly int $receivedBy,
        public readonly array $lines,
        public readonly ?string $notes = null,
        public readonly ?string $externalIdempotencyKey = null,
    ) {}
}
