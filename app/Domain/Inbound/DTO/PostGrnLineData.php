<?php

namespace App\Domain\Inbound\DTO;

final class PostGrnLineData
{
    public function __construct(
        public readonly int $poItemId,
        public readonly int $itemId,
        public readonly float $quantity,
        public readonly int $toLocationId,
        public readonly ?string $lotNo,
    ) {}
}
