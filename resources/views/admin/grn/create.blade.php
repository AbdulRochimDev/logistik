@extends('layouts.base', ['title' => 'Posting GRN', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%); margin:0 auto; display:grid; gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .table { width:100%;border-collapse:collapse; }
    .table th,.table td { padding:0.75rem 0.5rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left; }
    .info { padding:0.75rem 1rem;border-radius:12px;background:rgba(59,130,246,0.12);color:#1d4ed8;border:1px solid rgba(59,130,246,0.25); }
    .error { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.25); }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="info">
        <strong>Quick GRN:</strong> pilih inbound shipment & line PO, sistem otomatis menerapkan idempoten via StockService. Ulangi request yang sama → stok tidak bertambah ganda.
    </div>

    @if($errors->any())
        <div class="error">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin:0.5rem 0 0 1.25rem;">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.grn.store') }}" class="card" style="display:grid;gap:1.5rem;">
        @csrf
        <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
            <div>
                <label for="inbound_shipment_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Inbound Shipment</label>
                <select id="inbound_shipment_id" name="inbound_shipment_id" required style="width:100%;padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                    <option value="">Pilih inbound...</option>
                    @foreach($inboundShipments as $shipment)
                        <option value="{{ $shipment->id }}" @selected(old('inbound_shipment_id') == $shipment->id) data-po="{{ $shipment->purchase_order_id }}">
                            ASN #{{ $shipment->asn_no ?? $shipment->id }} · PO {{ $shipment->purchaseOrder?->po_no }} ({{ $shipment->purchaseOrder?->supplier?->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="received_at" style="font-weight:600;display:block;margin-bottom:0.35rem;">Tanggal Terima</label>
                <input id="received_at" name="received_at" type="datetime-local" value="{{ old('received_at', now()->format('Y-m-d\TH:i')) }}" required style="width:100%;padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            </div>
            <div>
                <label for="notes" style="font-weight:600;display:block;margin-bottom:0.35rem;">Catatan</label>
                <input id="notes" name="notes" type="text" value="{{ old('notes') }}" style="width:100%;padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            </div>
        </div>

        <div>
            <h2 style="margin:0 0 0.75rem;font-size:1.2rem;">Lines</h2>
            <div style="overflow-x:auto;">
                <table class="table" id="grn-lines-table">
                    <thead>
                        <tr>
                            <th>PO Item</th>
                            <th>Qty</th>
                            <th>Lokasi Tujuan</th>
                            <th>Lot No</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="grn-lines-body">
                        @php($oldLines = old('lines', []))
                        @if(empty($oldLines))
                            @php($oldLines = [[ 'po_item_id' => null, 'item_id' => null, 'qty' => 1, 'to_location_id' => $locations->first()->id ?? null, 'lot_no' => null ]])
                        @endif
                        @foreach($oldLines as $index => $line)
                            <tr>
                                <td>
                                    <select name="lines[{{ $index }}][po_item_id]" class="po-item-select" data-line="{{ $index }}" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                                        <option value="">Pilih line PO...</option>
                                        @foreach($poItems as $poItem)
                                            <option value="{{ $poItem->id }}" data-item-id="{{ $poItem->item_id }}" data-po="{{ $poItem->purchase_order_id }}" @selected($line['po_item_id'] == $poItem->id)>
                                                {{ $poItem->purchaseOrder?->po_no }} · {{ $poItem->item?->sku }} ({{ $poItem->item?->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="lines[{{ $index }}][item_id]" value="{{ $line['item_id'] ?? '' }}" />
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0.001" name="lines[{{ $index }}][qty]" value="{{ $line['qty'] ?? 1 }}" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
                                </td>
                                <td>
                                    <select name="lines[{{ $index }}][to_location_id]" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" @selected(($line['to_location_id'] ?? null) == $location->id)>
                                                {{ $location->warehouse?->code }} · {{ $location->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $index }}][lot_no]" value="{{ $line['lot_no'] ?? '' }}" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" placeholder="Opsional" />
                                </td>
                                <td style="text-align:right;">
                                    <button type="button" class="btn btn-neutral" data-action="remove-line">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-neutral" id="add-grn-line" style="margin-top:0.75rem;">Tambah Line</button>
        </div>

        <div style="display:flex;gap:1rem;">
            <button type="submit" class="btn btn-primary">Posting GRN</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-neutral">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const linesBody = document.getElementById('grn-lines-body');
        const addButton = document.getElementById('add-grn-line');
        const inboundSelect = document.getElementById('inbound_shipment_id');
        const poItemOptions = Array.from(document.querySelectorAll('.po-item-select option')).map(opt => ({
            value: opt.value,
            label: opt.textContent,
            po: opt.dataset.po ?? '',
            itemId: opt.dataset.itemId ?? ''
        })).filter(opt => opt.value);
        let index = linesBody.children.length;

        const refreshOptionsForSelect = (select) => {
            const poId = inboundSelect?.selectedOptions?.[0]?.dataset.po ?? '';
            const current = select.value;
            select.innerHTML = '<option value="">Pilih line PO...</option>';
            poItemOptions
                .filter(opt => !poId || opt.po === poId)
                .forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.value;
                    option.textContent = opt.label;
                    option.dataset.po = opt.po;
                    option.dataset.itemId = opt.itemId;
                    if (opt.value === current) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
        };

        if (inboundSelect) {
            inboundSelect.addEventListener('change', function () {
                document.querySelectorAll('.po-item-select').forEach(select => {
                    const previous = select.value;
                    refreshOptionsForSelect(select);
                    if (select.value !== previous) {
                        const lineIndex = select.dataset.line;
                        if (lineIndex) {
                            const hidden = select.parentElement.querySelector(`input[name="lines[${lineIndex}][item_id]"]`);
                            const selected = select.selectedOptions[0];
                            if (hidden) {
                                hidden.value = selected?.dataset.itemId ?? '';
                            }
                        }
                    }
                });
            });
        }

        document.querySelectorAll('.po-item-select').forEach(select => {
            refreshOptionsForSelect(select);
            const selected = select.selectedOptions[0];
            const lineIndex = select.dataset.line;
            if (selected && lineIndex) {
                const hidden = select.parentElement.querySelector(`input[name="lines[${lineIndex}][item_id]"]`);
                if (hidden) {
                    hidden.value = selected.dataset.itemId ?? '';
                }
            }
        });

        addButton?.addEventListener('click', function () {
            const row = document.createElement('tr');
            const poId = inboundSelect?.selectedOptions?.[0]?.dataset.po ?? '';

            const optionsHtml = poItemOptions
                .filter(opt => !poId || opt.po === poId)
                .map(opt => `<option value="${opt.value}" data-po="${opt.po}" data-item-id="${opt.itemId}">${opt.label}</option>`)
                .join('');

            row.innerHTML = `
                <td>
                    <select name="lines[${index}][po_item_id]" class="po-item-select" data-line="${index}" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                        <option value="">Pilih line PO...</option>
                        ${optionsHtml}
                    </select>
                    <input type="hidden" name="lines[${index}][item_id]" value="" />
                </td>
                <td><input type="number" step="0.001" min="0.001" name="lines[${index}][qty]" value="1" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" /></td>
                <td>
                    <select name="lines[${index}][to_location_id]" required style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->warehouse?->code }} · {{ $location->code }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="lines[${index}][lot_no]" placeholder="Opsional" style="padding:0.65rem 0.8rem;border-radius:10px;border:1px solid rgba(148,163,184,0.35);width:100%;" /></td>
                <td style="text-align:right;"><button type="button" class="btn btn-neutral" data-action="remove-line">Hapus</button></td>
            `;

            linesBody.appendChild(row);
            const select = row.querySelector('.po-item-select');
            if (select) {
                const selected = select.selectedOptions[0];
                const hidden = row.querySelector(`input[name="lines[${index}][item_id]"]`);
                if (hidden && selected) {
                    hidden.value = selected.dataset.itemId ?? '';
                }
            }

            index += 1;
        });

        linesBody?.addEventListener('change', function (event) {
            const target = event.target;
            if (target instanceof HTMLSelectElement && target.classList.contains('po-item-select')) {
                const selected = target.selectedOptions[0];
                const lineIndex = target.dataset.line;
                if (! lineIndex) {
                    return;
                }
                const hidden = target.parentElement.querySelector(`input[name="lines[${lineIndex}][item_id]"]`);
                if (hidden && selected) {
                    hidden.value = selected.dataset.itemId ?? '';
                }
            }
        });

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
