<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\Stock;
use App\Domain\Inventory\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * StockService is the single entry-point for mutating stock balances and logging movements.
 */
class StockService
{
    /**
     * @var array<string, array<int, array{direction: string, on_hand: float, allocated: float}>>
     */
    protected array $matrix = [
        'inbound_putaway' => [
            ['direction' => 'to', 'on_hand' => 1.0, 'allocated' => 0.0],
        ],
        'allocate' => [
            ['direction' => 'from', 'on_hand' => 0.0, 'allocated' => 1.0],
        ],
        'deallocate' => [
            ['direction' => 'from', 'on_hand' => 0.0, 'allocated' => -1.0],
        ],
        'pick' => [
            ['direction' => 'from', 'on_hand' => -1.0, 'allocated' => -1.0],
        ],
        'pack' => [
            ['direction' => 'from', 'on_hand' => 0.0, 'allocated' => -1.0],
        ],
        'ship' => [
            ['direction' => 'from', 'on_hand' => 0.0, 'allocated' => -1.0],
        ],
        'pod' => [
            ['direction' => 'from', 'on_hand' => 0.0, 'allocated' => -1.0],
        ],
        'transfer_out' => [
            ['direction' => 'from', 'on_hand' => -1.0, 'allocated' => 0.0],
            ['direction' => 'to', 'on_hand' => 1.0, 'allocated' => 0.0],
        ],
        'transfer_in' => [
            ['direction' => 'to', 'on_hand' => 1.0, 'allocated' => 0.0],
        ],
        'adjust_increase' => [
            ['direction' => 'to', 'on_hand' => 1.0, 'allocated' => 0.0],
        ],
        'adjust_decrease' => [
            ['direction' => 'from', 'on_hand' => -1.0, 'allocated' => 0.0],
        ],
        'cycle_count_increase' => [
            ['direction' => 'to', 'on_hand' => 1.0, 'allocated' => 0.0],
        ],
        'cycle_count_decrease' => [
            ['direction' => 'from', 'on_hand' => -1.0, 'allocated' => 0.0],
        ],
    ];

    /**
     * Execute a stock movement in a transactional, idempotent manner.
     */
    public function move(
        string $type,
        int $warehouseId,
        int $itemId,
        ?int $lotId,
        ?int $fromLocationId,
        ?int $toLocationId,
        float $qty,
        string $uom,
        string $refType,
        string $refId,
        ?int $actorUserId,
        $movedAt,
        ?string $remarks = null
    ): StockMovement {
        $this->assertQuantity($qty);
        $movedAt = $this->normalizeDate($movedAt);

        if (! isset($this->matrix[$type])) {
            throw new StockException("Unsupported stock movement type: {$type}");
        }

        return DB::transaction(function () use (
            $type,
            $warehouseId,
            $itemId,
            $lotId,
            $fromLocationId,
            $toLocationId,
            $qty,
            $uom,
            $refType,
            $refId,
            $actorUserId,
            $movedAt,
            $remarks
        ) {
            $existing = StockMovement::query()
                ->where('type', $type)
                ->where('ref_type', $refType)
                ->where('ref_id', $refId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $movementStocks = [];

            foreach ($this->matrix[$type] as $rule) {
                $locationId = $this->resolveLocationId($rule['direction'], $fromLocationId, $toLocationId);
                if ($locationId === null) {
                    throw new StockException("Location is required for movement type {$type} ({$rule['direction']}).");
                }

                $stock = $this->lockStock($warehouseId, $locationId, $itemId, $lotId);

                $this->applyDelta($stock, $qty, $rule['on_hand'], $rule['allocated']);
                $stock->save();

                $movementStocks[] = $stock;
            }

            $primaryStock = ($movementStocks[0] ?? $this->lockStock(
                $warehouseId,
                $fromLocationId ?? $toLocationId ?? throw new StockException('Primary stock context missing.'),
                $itemId,
                $lotId
            ));

            return StockMovement::query()->create([
                'stock_id' => $primaryStock->id,
                'type' => $type,
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'item_lot_id' => $lotId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'quantity' => $qty,
                'uom' => Str::upper($uom),
                'ref_type' => $refType,
                'ref_id' => $refId,
                'actor_user_id' => $actorUserId,
                'remarks' => $remarks,
                'moved_at' => $movedAt,
            ]);
        });
    }

    protected function assertQuantity(float $qty): void
    {
        if ($qty <= 0) {
            throw new StockException('Quantity must be greater than zero.');
        }
    }

    /**
     * @param  \DateTimeInterface|string  $value
     */
    protected function normalizeDate($value): Carbon
    {
        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    protected function resolveLocationId(string $direction, ?int $fromLocationId, ?int $toLocationId): ?int
    {
        return match ($direction) {
            'from' => $fromLocationId,
            'to' => $toLocationId,
            default => null,
        };
    }

    protected function lockStock(int $warehouseId, int $locationId, int $itemId, ?int $lotId): Stock
    {
        $query = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->where('item_id', $itemId)
            ->when($lotId, fn ($q) => $q->where('item_lot_id', $lotId))
            ->when(is_null($lotId), fn ($q) => $q->whereNull('item_lot_id'))
            ->lockForUpdate();

        $stock = $query->first();

        if (! $stock) {
            $stock = Stock::query()->create([
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'item_id' => $itemId,
                'item_lot_id' => $lotId,
                'qty_on_hand' => 0,
                'qty_allocated' => 0,
            ]);

            $stock->refresh();
        }

        return $stock;
    }

    protected function applyDelta(Stock $stock, float $qty, float $onHandFactor, float $allocatedFactor): void
    {
        $onHandDelta = $qty * $onHandFactor;
        $allocatedDelta = $qty * $allocatedFactor;

        $newOnHand = $stock->qty_on_hand + $onHandDelta;
        $newAllocated = $stock->qty_allocated + $allocatedDelta;

        if ($newOnHand < 0 || $newAllocated < 0) {
            throw new StockException('Resulting stock levels cannot be negative.');
        }

        $stock->qty_on_hand = $newOnHand;
        $stock->qty_allocated = $newAllocated;
    }
}
