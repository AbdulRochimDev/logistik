@php(
    $isEdit = isset($purchaseOrder))
@php(
    $lineDefaults = collect(old('lines', $isEdit ? $purchaseOrder->items->map(function ($line) {
        return [
            'id' => $line->id,
            'item_id' => $line->item_id,
            'uom' => $line->uom,
            'qty_ordered' => $line->ordered_qty,
        ];
    })->all() : [[
        'item_id' => $items->first()->id ?? null,
        'uom' => 'PCS',
        'qty_ordered' => 1,
    ]])) )
<form method="POST" action="{{ $isEdit ? route('admin.purchase-orders.update', $purchaseOrder) : route('admin.purchase-orders.store') }}" class="card" style="display:grid;gap:1.5rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="po_no" style="font-weight:600;display:block;margin-bottom:0.35rem;">PO Number</label>
            <input id="po_no" name="po_no" type="text" value="{{ old('po_no', $purchaseOrder->po_no ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('po_no')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="supplier_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Supplier</label>
            <select id="supplier_id" name="supplier_id" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id ?? null) == $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="warehouse_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Gudang</label>
            <select id="warehouse_id" name="warehouse_id" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $purchaseOrder->warehouse_id ?? null) == $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                @endforeach
            </select>
            @error('warehouse_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="status" style="font-weight:600;display:block;margin-bottom:0.35rem;">Status</label>
            @php($selectedStatus = old('status', $purchaseOrder->status ?? 'draft'))
            <select id="status" name="status" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                @foreach(['draft','approved','closed'] as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="eta" style="font-weight:600;display:block;margin-bottom:0.35rem;">ETA</label>
            <input id="eta" name="eta" type="date" value="{{ old('eta', optional($purchaseOrder->eta ?? null)->format('Y-m-d')) }}" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('eta')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="notes" style="font-weight:600;display:block;margin-bottom:0.35rem;">Catatan</label>
        <textarea id="notes" name="notes" rows="3" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">{{ old('notes', $purchaseOrder->notes ?? '') }}</textarea>
        @error('notes')
            <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <h2 style="margin:0 0 0.75rem;font-size:1.2rem;">Lines</h2>
        @error('lines')
            <div style="margin-bottom:0.75rem;color:#b91c1c;">{{ $message }}</div>
        @enderror
        <div style="overflow-x:auto;">
            <table class="table" id="po-lines-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>UOM</th>
                        <th>Qty Order</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="po-lines-body">
                @foreach($lineDefaults as $index => $line)
                    <tr>
                        <td>
                            <select name="lines[{{ $index }}][item_id]" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                                @foreach($items as $itemOption)
                                    <option value="{{ $itemOption->id }}" @selected($line['item_id'] == $itemOption->id)>{{ $itemOption->sku }} — {{ $itemOption->name }}</option>
                                @endforeach
                            </select>
                            @if(!empty($line['id']))
                                <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line['id'] }}" />
                            @endif
                        </td>
                        <td>
                            <input type="text" name="lines[{{ $index }}][uom]" value="{{ $line['uom'] ?? 'PCS' }}" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
                        </td>
                        <td>
                            <input type="number" step="0.001" min="0.001" name="lines[{{ $index }}][qty_ordered]" value="{{ $line['qty_ordered'] ?? 1 }}" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
                        </td>
                        <td style="text-align:right;">
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
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const linesBody = document.getElementById('po-lines-body');
        const addLineBtn = document.getElementById('add-line');
        let lineIndex = {{ $lineDefaults->count() }};

        if (addLineBtn) {
            addLineBtn.addEventListener('click', function () {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <select name="lines[${lineIndex}][item_id]" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                            @foreach($items as $itemOption)
                                <option value="{{ $itemOption->id }}">{{ $itemOption->sku }} — {{ $itemOption->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" name="lines[${lineIndex}][uom]" value="PCS" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" /></td>
                    <td><input type="number" min="0.001" step="0.001" name="lines[${lineIndex}][qty_ordered]" value="1" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" /></td>
                    <td style="text-align:right;"><button type="button" class="btn btn-neutral" data-action="remove-line">Hapus</button></td>
                `;
                linesBody.appendChild(row);
                lineIndex += 1;
            });
        }

        linesBody?.addEventListener('click', function (event) {
            const target = event.target;
            if (target instanceof HTMLElement && target.dataset.action === 'remove-line') {
                const row = target.closest('tr');
                if (row && linesBody.children.length > 1) {
                    row.remove();
                }
            }
        });
    });
</script>
@endpush
