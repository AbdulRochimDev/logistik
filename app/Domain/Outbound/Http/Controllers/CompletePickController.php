<?php

namespace App\Domain\Outbound\Http\Controllers;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Outbound\DTO\PickLineData;
use App\Domain\Outbound\Http\Requests\CompletePickRequest;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Services\OutboundService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompletePickController
{
    public function __construct(private readonly OutboundService $service) {}

    public function __invoke(CompletePickRequest $request): JsonResponse
    {
        /** @var ShipmentItem $shipmentItem */
        $shipmentItem = ShipmentItem::query()
            ->with('shipment')
            ->findOrFail($request->integer('shipment_item_id'));

        $idempotencyKey = $this->resolveIdempotencyKey($request, $shipmentItem->id, $request->input('qty'), $request->input('picked_at'));

        $dto = new PickLineData(
            shipmentItemId: $shipmentItem->id,
            quantity: (float) $request->input('qty'),
            idempotencyKey: $idempotencyKey,
            pickedAt: CarbonImmutable::parse($request->input('picked_at')),
            actorUserId: $request->user()?->id,
            remarks: $request->input('remarks')
        );

        try {
            $result = $this->service->completePick($dto);
        } catch (StockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'data' => [
                'movement_id' => $result['movement']->id,
                'shipment_item' => $result['shipment_item'],
                'shipment' => $result['shipment'],
            ],
        ], $result['movement']->wasRecentlyCreated ? 201 : 200);
    }

    private function resolveIdempotencyKey(Request $request, int $shipmentItemId, float $qty, string $timestamp): string
    {
        $header = trim((string) $request->headers->get('X-Idempotency-Key', ''));

        return $header !== ''
            ? $header
            : hash('sha256', sprintf('PICK|%d|%s|%s', $shipmentItemId, $qty, $timestamp));
    }
}
