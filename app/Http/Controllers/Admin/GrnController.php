<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inbound\DTO\PostGrnData;
use App\Domain\Inbound\DTO\PostGrnLineData;
use App\Domain\Inbound\Http\Requests\PostGrnRequest;
use App\Domain\Inbound\Models\InboundShipment;
use App\Domain\Inbound\Models\PoItem;
use App\Domain\Inbound\Services\GrnService;
use App\Domain\Inventory\Exceptions\StockException;
use App\Domain\Inventory\Models\Location;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GrnController extends Controller
{
    public function create(): View
    {
        $inboundShipments = InboundShipment::query()
            ->with(['purchaseOrder.supplier'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $locations = Location::query()
            ->with('warehouse')
            ->orderBy('warehouse_id')
            ->orderBy('code')
            ->get();

        $poItems = PoItem::query()
            ->with(['item', 'purchaseOrder.supplier', 'purchaseOrder.warehouse'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin.grn.create', compact('inboundShipments', 'locations', 'poItems'));
    }

    public function store(PostGrnRequest $request, GrnService $service): RedirectResponse
    {
        $actorId = Auth::id();
        if (! $actorId) {
            return redirect()->back()->withErrors(['user' => 'Pengguna tidak dikenal untuk melakukan posting GRN.']);
        }

        $validated = $request->validated();
        $external = $request->headers->get('X-Idempotency-Key');

        try {
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
                externalIdempotencyKey: $external ? Str::of($external)->trim()->value() : null,
            );

            $result = $service->post($dto);
        } catch (StockException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['grn' => $exception->getMessage()]);
        }

        $metadata = $result['metadata'];

        return redirect()
            ->route('admin.dashboard')
            ->with('status', sprintf(
                'GRN diposting. Processed: %d, skipped: %d. Kunci: %s',
                $metadata['lines_processed'],
                $metadata['lines_skipped'],
                $metadata['external_idempotency_key']
            ));
    }
}
