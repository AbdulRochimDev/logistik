<?php

namespace App\Domain\Outbound\DTO;

use Carbon\CarbonImmutable;

final class PickLineData
{
    public function __construct(
        public readonly int $shipmentItemId,
        public readonly float $quantity,
        public readonly string $idempotencyKey,
        public readonly CarbonImmutable $pickedAt,
        public readonly ?int $actorUserId = null,
        public readonly ?string $remarks = null,
    ) {}
}
