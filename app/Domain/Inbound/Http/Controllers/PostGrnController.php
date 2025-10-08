<?php

namespace App\Domain\Inbound\Http\Controllers;

use App\Domain\Inbound\DTO\PostGrnData;
use App\Domain\Inbound\DTO\PostGrnLineData;
use App\Domain\Inbound\Http\Requests\PostGrnRequest;
use App\Domain\Inbound\Services\GrnService;
use App\Domain\Inventory\Exceptions\StockException;
use App\Support\Idempotency\Exceptions\IdempotencyException;
use App\Support\Idempotency\IdempotencyManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class PostGrnController
{
    public function __construct(
        private readonly GrnService $service,
        private readonly IdempotencyManager $idempotency
    ) {}

    public function store(PostGrnRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $actorId = $request->user()?->id;
            if (! $actorId) {
                throw new StockException('Unable to determine acting user for GRN posting.');
            }

            try {
                $externalKeyHeader = $this->idempotency->resolve($request, 'inbound.grn', [
                    $validated['inbound_shipment_id'],
                    collect($validated['lines'])->map(fn ($line) => [
                        $line['po_item_id'],
                        $line['item_id'],
                        $line['qty'],
                        $line['to_location_id'],
                        $line['lot_no'] ?? null,
                    ])->toArray(),
                ]);
            } catch (IdempotencyException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 409);
            }

            $lines = array_map(
                fn (array $line) => new PostGrnLineData(
                    poItemId: (int) $line['po_item_id'],
                    itemId: (int) $line['item_id'],
                    quantity: (float) $line['qty'],
                    toLocationId: (int) $line['to_location_id'],
                    lotNo: $line['lot_no'] ?? null,
                ),
                $validated['lines']
            );

            $dto = new PostGrnData(
                grnHeaderId: $validated['grn_header_id'] ?? null,
                inboundShipmentId: (int) $validated['inbound_shipment_id'],
                receivedAt: CarbonImmutable::parse($validated['received_at']),
                receivedBy: (int) $actorId,
                lines: $lines,
                notes: $validated['notes'] ?? null,
                externalIdempotencyKey: $externalKeyHeader,
            );

            $result = $this->service->post($dto);
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        $header = $result['header'];
        $metadata = $result['metadata'];
        $movements = collect($result['movements']);

        return response()->json([
            'data' => [
                'grn_id' => $header->id,
                'grn_no' => $header->grn_no,
                'external_idempotency_key' => $metadata['external_idempotency_key'],
                'lines_processed' => $metadata['lines_processed'],
                'lines_skipped' => $metadata['lines_skipped'],
                'movements' => $movements->map(fn ($movement) => [
                    'id' => $movement->id,
                    'stock_id' => $movement->stock_id,
                    'quantity' => $movement->quantity,
                    'location_id' => $movement->to_location_id,
                ]),
            ],
        ], $metadata['created'] ? 201 : 200);
    }

}
