<?php

namespace App\Domain\Outbound\DTO;

use Carbon\CarbonImmutable;

final class ShipmentPodData
{
    public function __construct(
        public readonly int $shipmentId,
        public readonly string $signerName,
        public readonly CarbonImmutable $signedAt,
        public readonly string $idempotencyKey,
        public readonly ?int $actorUserId = null,
        public readonly ?string $signerId = null,
        public readonly ?string $photoPath = null,
        public readonly ?string $signaturePath = null,
        public readonly ?string $notes = null,
        public readonly ?array $meta = null,
    ) {}
}
