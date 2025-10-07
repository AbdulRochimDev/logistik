@php
    $isEdit = isset($shipment);
    $lineDefaults = collect(old('lines', $isEdit ? $shipment->items->map(fn ($item) => [
        'id' => $item->id,
        'item_id' => $item->item_id,
        'item_lot_id' => $item->item_lot_id,
        'from_location_id' => $item->from_location_id,
        'qty_planned' => $item->qty_planned,
    ])->all() : [[
        'item_id' => $items->first()->id ?? null,
        'item_lot_id' => null,
        'from_location_id' => $locations->first()->id ?? null,
        'qty_planned' => 0,
    ]]))
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.shipments.update', $shipment) : route('admin.shipments.store') }}" class="card" style="display:grid;gap:1.5rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="warehouse_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Gudang</label>
            <select id="warehouse_id" name="warehouse_id" style="padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $shipment->warehouse_id ?? $warehouse->id) == $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                @endforeach
            </select>
            @error('warehouse_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="outbound_shipment_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Outbound Shipment</label>
            <select id="outbound_shipment_id" name="outbound_shipment_id" style="padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                @foreach($outboundShipments as $outbound)
                    @php
                        $label = $outbound->salesOrder
                            ? $outbound->salesOrder->so_no.' — '.$outbound->salesOrder->customer?->name
                            : 'Outbound '.$outbound->id;
                    @endphp
                    <option value="{{ $outbound->id }}" @selected(old('outbound_shipment_id', $shipment->outbound_shipment_id ?? $outboundShipments->first()?->id) == $outbound->id)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('outbound_shipment_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="driver_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Driver</label>
            <select id="driver_id" name="driver_id" style="padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                <option value="">—</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected(old('driver_id', $shipment->driver_id ?? null) == $driver->id)>{{ $driver->name }} ({{ ucfirst($driver->status) }})</option>
                @endforeach
            </select>
            @error('driver_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="vehicle_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Kendaraan</label>
            <select id="vehicle_id" name="vehicle_id" style="padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                <option value="">—</option>
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $shipment->vehicle_id ?? null) == $vehicle->id)>{{ $vehicle->plate_no }} ({{ ucfirst($vehicle->status) }})</option>
                @endforeach
            </select>
            @error('vehicle_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="planned_at" style="font-weight:600;display:block;margin-bottom:0.35rem;">Rencana Tanggal</label>
            <input id="planned_at" type="datetime-local" name="planned_at" value="{{ old('planned_at', optional($shipment->planned_at ?? null)->format('Y-m-d\TH:i')) }}" style="padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('planned_at')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <h2 style="margin:0 0 0.75rem;font-size:1.2rem;">Shipment Lines</h2>
        @error('lines')
            <div style="color:#b91c1c;margin-bottom:0.75rem;">{{ $message }}</div>
        @enderror
        <div style="overflow-x:auto;">
            <table class="table" id="shipment-lines-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:0.65rem;">Item</th>
                        <th style="text-align:left;padding:0.65rem;">Lot</th>
                        <th style="text-align:left;padding:0.65rem;">Lokasi</th>
                        <th style="text-align:left;padding:0.65rem;">Qty Planned</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="shipment-lines-body">
                @foreach($lineDefaults as $index => $line)
                    <tr>
                        <td style="padding:0.5rem;">
                            <select name="lines[{{ $index }}][item_id]" class="item-select" data-line="{{ $index }}" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-lot-tracked="{{ $item->is_lot_tracked ? '1' : '0' }}" @selected($line['item_id'] == $item->id)>{{ $item->sku }} — {{ $item->name }}</option>
                                @endforeach
                            </select>
                            @if(!empty($line['id']))
                                <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line['id'] }}" />
                            @endif
                            @error("lines.$index.item_id")
                                <p style="color:#b91c1c;font-size:0.85rem;margin-top:0.35rem;">{{ $message }}</p>
                            @enderror
                        </td>
                        <td style="padding:0.5rem;">
                            <select name="lines[{{ $index }}][item_lot_id]" class="lot-select" data-line="{{ $index }}" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                                <option value="">—</option>
                                @foreach($items as $item)
                                    @foreach($item->lots as $lot)
                                        <option value="{{ $lot->id }}" data-item="{{ $item->id }}" @selected($line['item_lot_id'] == $lot->id)>Lot {{ $lot->lot_no }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            @error("lines.$index.item_lot_id")
                                <p style="color:#b91c1c;font-size:0.85rem;margin-top:0.35rem;">{{ $message }}</p>
                            @enderror
                        </td>
                        <td style="padding:0.5rem;">
                            <select name="lines[{{ $index }}][from_location_id]" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                                <option value="">—</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" @selected(($line['from_location_id'] ?? null) == $location->id)>{{ $location->code }}</option>
                                @endforeach
                            </select>
                            @error("lines.$index.from_location_id")
                                <p style="color:#b91c1c;font-size:0.85rem;margin-top:0.35rem;">{{ $message }}</p>
                            @enderror
                        </td>
                        <td style="padding:0.5rem;">
                            <input type="number" step="0.001" min="0" name="lines[{{ $index }}][qty_planned]" value="{{ $line['qty_planned'] }}" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
                            @error("lines.$index.qty_planned")
                                <p style="color:#b91c1c;font-size:0.85rem;margin-top:0.35rem;">{{ $message }}</p>
                            @enderror
                        </td>
                        <td style="text-align:right;padding:0.5rem;">
                            <button type="button" class="btn btn-neutral" data-action="remove-line">Hapus</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-neutral" id="add-line" style="margin-top:0.75rem;">Tambah Line</button>
    </div>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.shipments.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const body = document.getElementById('shipment-lines-body');
        const addButton = document.getElementById('add-line');
        let lineIndex = body.children.length;

        const items = @json($items->map(fn ($item) => [
            'id' => $item->id,
            'sku' => $item->sku,
            'name' => $item->name,
            'is_lot_tracked' => $item->is_lot_tracked,
        ]));
        const lots = @json($items->flatMap(fn ($item) => $item->lots->map(fn ($lot) => [
            'id' => $lot->id,
            'item_id' => $item->id,
            'lot_no' => $lot->lot_no,
        ])));
        const locations = @json($locations->map(fn ($location) => [
            'id' => $location->id,
            'code' => $location->code,
        ]));

        const renderRow = (index) => {
            const itemOptions = items.map(item => `<option value="${item.id}" data-lot-tracked="${item.is_lot_tracked ? '1' : '0'}">${item.sku} — ${item.name}</option>`).join('');
            const lotOptions = ['<option value="">—</option>'].concat(lots.map(lot => `<option value="${lot.id}" data-item="${lot.item_id}">Lot ${lot.lot_no}</option>`)).join('');
            const locationOptions = ['<option value="">—</option>'].concat(locations.map(location => `<option value="${location.id}">${location.code}</option>`)).join('');

            return `
                <tr>
                    <td style="padding:0.5rem;">
                        <select name="lines[${index}][item_id]" class="item-select" data-line="${index}" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                            ${itemOptions}
                        </select>
                    </td>
                    <td style="padding:0.5rem;">
                        <select name="lines[${index}][item_lot_id]" class="lot-select" data-line="${index}" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                            ${lotOptions}
                        </select>
                    </td>
                    <td style="padding:0.5rem;">
                        <select name="lines[${index}][from_location_id]" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                            ${locationOptions}
                        </select>
                    </td>
                    <td style="padding:0.5rem;">
                        <input type="number" step="0.001" min="0" name="lines[${index}][qty_planned]" value="0" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
                    </td>
                    <td style="text-align:right;padding:0.5rem;">
                        <button type="button" class="btn btn-neutral" data-action="remove-line">Hapus</button>
                    </td>
                </tr>`;
        };

        const refreshLotOptions = (row, itemId) => {
            const lotSelect = row.querySelector('.lot-select');
            if (!lotSelect) return;
            Array.from(lotSelect.options).forEach(option => {
                const optionItem = option.dataset.item;
                option.hidden = option.value !== '' && optionItem !== String(itemId);
            });
        };

        body.addEventListener('change', (event) => {
            if (event.target.matches('.item-select')) {
                const row = event.target.closest('tr');
                refreshLotOptions(row, event.target.value);
            }
        });

        Array.from(body.querySelectorAll('.item-select')).forEach(select => {
            const row = select.closest('tr');
            refreshLotOptions(row, select.value);
        });

        addButton?.addEventListener('click', () => {
            const rowHtml = renderRow(lineIndex);
            body.insertAdjacentHTML('beforeend', rowHtml);
            const newRow = body.lastElementChild;
            refreshLotOptions(newRow, newRow.querySelector('.item-select').value);
            lineIndex += 1;
        });

        body.addEventListener('click', (event) => {
            if (event.target.dataset.action === 'remove-line') {
                const row = event.target.closest('tr');
                if (body.children.length > 1) {
                    row.remove();
                }
            }
        });
    });
</script>
@endpush
