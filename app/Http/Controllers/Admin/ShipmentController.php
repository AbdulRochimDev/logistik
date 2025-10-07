<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Models\Item;
use App\Domain\Inventory\Models\Location;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Outbound\DTO\ShipmentPodData;
use App\Domain\Outbound\Models\Driver;
use App\Domain\Outbound\Models\OutboundShipment;
use App\Domain\Outbound\Models\Shipment;
use App\Domain\Outbound\Models\ShipmentItem;
use App\Domain\Outbound\Models\Vehicle;
use App\Domain\Outbound\Services\OutboundService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShipmentRequest;
use App\Http\Requests\Admin\UpdateShipmentRequest;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function __construct(private readonly OutboundService $outboundService) {}

    public function index(Request $request): View
    {
        $shipments = Shipment::query()
            ->with(['driver', 'vehicle', 'items', 'outboundShipment.salesOrder.customer'])
            ->when($request->string('status')->trim(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('driver_id'), fn ($query, $driverId) => $query->where('driver_id', $driverId))
            ->when($request->date('date_from'), fn ($query, $date) => $query->whereDate('planned_at', '>=', $date))
            ->when($request->date('date_to'), fn ($query, $date) => $query->whereDate('planned_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $drivers = Driver::query()->orderBy('name')->get();
        $statuses = ['draft', 'allocated', 'dispatched', 'delivered'];

        return view('admin.shipments.index', compact('shipments', 'drivers', 'statuses'));
    }

    public function create(): View
    {
        return view('admin.shipments.create', $this->formData());
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $shipment = DB::transaction(function () use ($data) {
            $this->ensureDriverVehicle($data['driver_id'] ?? null, $data['vehicle_id'] ?? null);

            $shipment = Shipment::query()->create([
                'outbound_shipment_id' => $data['outbound_shipment_id'],
                'warehouse_id' => $data['warehouse_id'],
                'driver_id' => $data['driver_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'planned_at' => $data['planned_at'] ?? null,
                'status' => 'draft',
                'shipment_no' => Str::upper(Str::random(10)),
            ]);

            $this->syncLines($shipment, $data['lines']);

            return $shipment;
        });

        return redirect()->route('admin.shipments.show', $shipment)->with('status', 'Shipment berhasil dibuat.');
    }

    public function show(Shipment $shipment): View
    {
        $shipment->load(['items.item', 'items.lot', 'items.fromLocation', 'driver', 'vehicle', 'proofOfDelivery', 'outboundShipment.salesOrder.customer']);

        return view('admin.shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment): View|RedirectResponse
    {
        if (! $this->canModify($shipment)) {
            return redirect()->route('admin.shipments.show', $shipment)
                ->withErrors(['edit' => 'Shipment tidak dapat diedit setelah dispatched/delivered.']);
        }

        $shipment->load('items');

        return view('admin.shipments.edit', array_merge($this->formData(), compact('shipment')));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validated();

        if (! $this->canModify($shipment)) {
            return redirect()->route('admin.shipments.show', $shipment)
                ->withErrors(['update' => 'Shipment tidak dapat diubah setelah dispatched/delivered.']);
        }

        if (! $this->canAssignDriver($shipment) && ($data['driver_id'] ?? null) !== $shipment->driver_id) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['driver_id' => 'Driver hanya dapat diubah pada shipment draft atau allocated.']);
        }

        if (! $this->canAssignDriver($shipment) && ($data['vehicle_id'] ?? null) !== $shipment->vehicle_id) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['vehicle_id' => 'Kendaraan hanya dapat diubah pada shipment draft atau allocated.']);
        }

        DB::transaction(function () use ($shipment, $data) {
            $this->ensureDriverVehicle($data['driver_id'] ?? null, $data['vehicle_id'] ?? null);

            $shipment->update([
                'outbound_shipment_id' => $data['outbound_shipment_id'],
                'warehouse_id' => $data['warehouse_id'],
                'driver_id' => $data['driver_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'planned_at' => $data['planned_at'] ?? null,
            ]);

            if (! empty($data['lines'])) {
                $this->syncLines($shipment, $data['lines']);
            }
        });

        return redirect()->route('admin.shipments.show', $shipment)->with('status', 'Shipment berhasil diperbarui.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        if ($shipment->status !== 'draft') {
            return redirect()->route('admin.shipments.index')
                ->withErrors(['delete' => 'Shipment sudah memiliki proses outbound dan tidak dapat dihapus.']);
        }

        $shipment->delete();

        return redirect()->route('admin.shipments.index')->with('status', 'Shipment berhasil dihapus.');
    }

    public function dispatch(Request $request, Shipment $shipment): RedirectResponse
    {
        if ($shipment->status === 'delivered') {
            return redirect()->route('admin.shipments.show', $shipment)
                ->withErrors(['dispatch' => 'Shipment sudah delivered.']);
        }

        if (! $shipment->driver_id || ! $shipment->vehicle_id) {
            return redirect()->route('admin.shipments.show', $shipment)
                ->withErrors(['dispatch' => 'Assign driver dan kendaraan terlebih dahulu sebelum dispatch.']);
        }

        $idempotencyKey = $request->headers->get('X-Idempotency-Key');
        $idempotencyKey = $idempotencyKey ?: hash('sha256', sprintf('ADMIN-DISPATCH|%d', $shipment->id));

        $result = $this->outboundService->dispatch(
            shipment: $shipment,
            idempotencyKey: $idempotencyKey,
            dispatchedAt: CarbonImmutable::now(),
            actorUserId: $request->user()?->id
        );

        $latestShipment = $result['shipment'];
        $message = match (true) {
            $latestShipment->status === 'delivered' => 'Shipment sudah delivered.',
            $result['status_changed'] => 'Shipment berhasil di-dispatch.',
            default => 'Shipment sudah dalam status dispatched.',
        };

        return redirect()->route('admin.shipments.show', $shipment)->with('status', $message);
    }

    public function deliver(Request $request, Shipment $shipment): RedirectResponse
    {
        if (! in_array($shipment->status, ['dispatched', 'delivered'], true)) {
            return redirect()->route('admin.shipments.show', $shipment)
                ->withErrors(['deliver' => 'Shipment harus di-dispatch sebelum dapat ditandai delivered.']);
        }

        $idempotencyKey = $request->headers->get('X-Idempotency-Key');
        $idempotencyKey = $idempotencyKey ?: hash('sha256', sprintf('ADMIN-POD|%d', $shipment->id));

        $dto = new ShipmentPodData(
            shipmentId: $shipment->id,
            signerName: 'Admin Confirmation',
            signedAt: CarbonImmutable::now(),
            idempotencyKey: $idempotencyKey,
            actorUserId: $request->user()?->id,
            notes: 'Ditandai selesai oleh admin',
            meta: ['source' => 'admin'],
        );

        $result = $this->outboundService->deliver($dto);

        $message = $result['created']
            ? 'Shipment ditandai delivered.'
            : 'Shipment sudah delivered.';

        return redirect()->route('admin.shipments.show', $shipment)->with('status', $message);
    }

    private function formData(): array
    {
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $drivers = Driver::query()->orderBy('name')->get();
        $vehicles = Vehicle::query()->orderBy('plate_no')->get();
        $items = Item::query()->with('lots')->orderBy('sku')->get();
        $locations = Location::query()->orderBy('code')->get();
        $outboundShipments = OutboundShipment::query()
            ->with(['salesOrder.customer'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return compact('warehouses', 'drivers', 'vehicles', 'items', 'locations', 'outboundShipments');
    }

    private function canModify(Shipment $shipment): bool
    {
        return in_array($shipment->status, ['draft', 'allocated'], true);
    }

    private function canAssignDriver(Shipment $shipment): bool
    {
        return in_array($shipment->status, ['draft', 'allocated'], true);
    }

    private function ensureDriverVehicle(?int $driverId, ?int $vehicleId): void
    {
        if ($driverId) {
            $driver = Driver::query()->find($driverId);
            if (! $driver || $driver->status !== 'active') {
                throw ValidationException::withMessages(['driver_id' => 'Driver harus dalam status aktif.']);
            }
        }

        if ($vehicleId) {
            $vehicle = Vehicle::query()->find($vehicleId);
            if (! $vehicle || $vehicle->status !== 'active') {
                throw ValidationException::withMessages(['vehicle_id' => 'Kendaraan harus dalam status aktif.']);
            }
        }
    }

    private function syncLines(Shipment $shipment, array $lines): void
    {
        if (! $this->canModify($shipment)) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, ShipmentItem> $existing */
        $existing = $shipment->items()->lockForUpdate()->get()->keyBy('id');
        $keepIds = [];

        foreach ($lines as $line) {
            $payload = [
                'item_id' => $line['item_id'],
                'item_lot_id' => ! empty($line['item_lot_id']) ? (int) $line['item_lot_id'] : null,
                'from_location_id' => ! empty($line['from_location_id']) ? (int) $line['from_location_id'] : null,
                'qty_planned' => $line['qty_planned'],
            ];

            if (! empty($line['id']) && $existing->has((int) $line['id'])) {
                /** @var ShipmentItem|null $item */
                $item = $existing->get((int) $line['id']);
                if ($item === null) {
                    continue;
                }
                $item->update($payload);
                $keepIds[] = $item->id;
            } else {
                /** @var ShipmentItem $created */
                $created = $shipment->items()->create($payload);
                $keepIds[] = $created->id;
            }
        }

        if ($keepIds === []) {
            $shipment->items()->delete();
        } else {
            $shipment->items()->whereNotIn('id', $keepIds)->delete();
        }
    }
}
