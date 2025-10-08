@extends('layouts.base', ['title' => 'Detail Shipment', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%);margin:0 auto;display:grid;gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .status-pill { display:inline-flex;align-items:center;padding:0.4rem 0.9rem;border-radius:999px;font-size:0.9rem;font-weight:600; }
    .status-draft { background:rgba(148,163,184,0.25);color:#1f2937; }
    .status-allocated { background:rgba(59,130,246,0.18);color:#1d4ed8; }
    .status-dispatched { background:rgba(249,115,22,0.16);color:#c2410c; }
    .status-delivered { background:rgba(34,197,94,0.18);color:#047857; }
    .info-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.25rem;margin-top:1rem; }
    .info-item { display:flex;flex-direction:column;gap:0.35rem;background:rgba(241,245,249,0.6);padding:1rem;border-radius:14px; }
    .meta-label { font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em;color:#64748b; }
    .table { width:100%;border-collapse:collapse;margin-top:1rem; }
    .table th,.table td { padding:0.75rem 0.6rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left;vertical-align:middle; }
    .status-message { padding:0.75rem 1rem;border-radius:12px;background:rgba(34,197,94,0.12);color:#047857;border:1px solid rgba(34,197,94,0.25); }
    .error-message { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.22); }
    .actions-shell { display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:1.25rem; }
    .history-box { background:rgba(148,163,184,0.08);border-radius:14px;padding:1rem;display:grid;gap:0.75rem; }
    .progress-card { margin-top:1.5rem;background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.25);border-radius:16px;padding:1.25rem;display:grid;gap:0.75rem; }
    .progress-header { display:flex;justify-content:space-between;align-items:center;font-weight:600;color:#0f172a; }
    .progress-bar { width:100%;height:12px;border-radius:999px;background:rgba(148,163,184,0.25);overflow:hidden; }
    .progress-bar-fill { height:100%;background:linear-gradient(135deg,#0ea5e9,#22c55e);transition:width 0.3s ease; }
    .pod-actions { display:flex;align-items:center;gap:0.5rem;margin-top:0.65rem;flex-wrap:wrap; }
    .pod-replay-badge { display:inline-flex;align-items:center;padding:0.25rem 0.8rem;border-radius:999px;background:linear-gradient(135deg,rgba(236,72,153,0.15),rgba(59,130,246,0.18));color:#0f172a;font-size:0.78rem;font-weight:600;border:1px solid rgba(59,130,246,0.2); }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:1.5rem;flex-wrap:wrap;align-items:center;">
            <div>
                <h1 style="margin:0;font-size:1.95rem;">Shipment {{ $shipment->shipment_no ?? ('SHP-' . str_pad($shipment->id, 4, '0', STR_PAD_LEFT)) }}</h1>
                <p style="margin:0.35rem 0 0;color:#64748b;">Pantau progres lini shipment dan aksi cepat.</p>
            </div>
            <span class="status-pill status-{{ $shipment->status }}" data-shipment-status="{{ $shipment->id }}">{{ ucfirst($shipment->status) }}</span>
        </div>

        @if ($message = session('status'))
            <div class="status-message" style="margin-top:1.25rem;">{{ $message }}</div>
        @endif
        @if ($errors->any())
            <div class="error-message" style="margin-top:1.25rem;">{{ $errors->first() }}</div>
        @endif

        <div class="info-grid">
            <div class="info-item">
                <span class="meta-label">Warehouse</span>
                <span>{{ $shipment->warehouse?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="meta-label">Outbound</span>
                <span>{{ $shipment->outboundShipment?->salesOrder?->so_no ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="meta-label">Planned At</span>
                <span>{{ optional($shipment->planned_at)->format('d M Y H:i') ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="meta-label">Driver</span>
                <span>{{ $shipment->driver?->name ?? 'Belum di-assign' }}</span>
            </div>
            <div class="info-item">
                <span class="meta-label">Kendaraan</span>
                <span>{{ $shipment->vehicle?->plate_no ?? 'Belum di-assign' }}</span>
            </div>
        </div>

        @php
            $totalPlanned = (float) $shipment->items->sum('qty_planned');
            $totalPicked = (float) $shipment->items->sum('qty_picked');
            $progressWidth = $totalPlanned > 0 ? min(($totalPicked / $totalPlanned) * 100, 100) : 0;
        @endphp

        <div class="progress-card" data-shipment-progress data-shipment-id="{{ $shipment->id }}" data-total-planned="{{ $totalPlanned }}" data-total-picked="{{ $totalPicked }}">
            <div class="progress-header">
                <span>Progress Pick</span>
                <span data-progress-label>{{ number_format($totalPicked, 2) }} / {{ number_format($totalPlanned, 2) }} Picked</span>
            </div>
            <div class="progress-bar">
                <div class="progress-bar-fill" data-progress-fill style="width: {{ number_format($progressWidth, 1) }}%;"></div>
            </div>
        </div>

        <div class="actions-shell">
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-neutral">Kembali</a>
            <a href="{{ route('admin.shipments.edit', $shipment) }}" class="btn btn-neutral">Assign / Edit</a>
            @if(in_array($shipment->status, ['draft','allocated'], true))
                <form method="POST" action="{{ route('admin.shipments.dispatch', $shipment) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Dispatch Shipment</button>
                </form>
            @endif
            @if($shipment->status === 'dispatched')
                <form method="POST" action="{{ route('admin.shipments.deliver', $shipment) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Mark Delivered (Admin)</button>
                </form>
            @elseif($shipment->status === 'delivered')
                <form method="POST" action="{{ route('admin.shipments.deliver', $shipment) }}">
                    @csrf
                    <button class="btn btn-neutral" type="submit">Replay Deliver</button>
                </form>
            @endif
            @if($shipment->status === 'draft')
                <form method="POST" action="{{ route('admin.shipments.destroy', $shipment) }}" onsubmit="return confirm('Hapus shipment ini?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-neutral" style="border:1px solid rgba(239,68,68,0.35);color:#ef4444;" type="submit">Hapus</button>
                </form>
            @endif
        </div>

        <div style="margin-top:1.75rem;">
            <h2 style="margin:0 0 0.65rem;font-size:1.35rem;">Shipment Lines</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Lot</th>
                        <th>Lokasi</th>
                        <th>Qty Planned</th>
                        <th>Qty Picked</th>
                        <th>Qty Delivered</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($shipment->items as $item)
                    <tr data-shipment-item-row data-shipment-item-id="{{ $item->id }}" data-qty-planned="{{ (float) $item->qty_planned }}" data-qty-picked="{{ (float) $item->qty_picked }}" data-qty-delivered="{{ (float) $item->qty_delivered }}">
                        <td>{{ $item->item?->sku }} — {{ $item->item?->name }}</td>
                        <td>{{ $item->lot?->lot_no ?? '—' }}</td>
                        <td>{{ $item->fromLocation?->code ?? '—' }}</td>
                        <td data-field="qty_planned">{{ number_format((float) $item->qty_planned, 2) }}</td>
                        <td data-field="qty_picked" data-value="{{ (float) $item->qty_picked }}">{{ number_format((float) $item->qty_picked, 2) }}</td>
                        <td data-field="qty_delivered" data-value="{{ (float) $item->qty_delivered }}">{{ number_format((float) $item->qty_delivered, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada line pada shipment ini.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="history-box" style="margin-top:1.75rem;">
            <div>
                <strong>Riwayat Status</strong>
                <p style="margin:0.35rem 0 0;color:#64748b;font-size:0.95rem;">
                    Dispatched pada: {{ optional($shipment->dispatched_at)->format('d M Y H:i') ?? '—' }}<br>
                    Delivered pada: {{ optional($shipment->delivered_at)->format('d M Y H:i') ?? '—' }}
                </p>
            </div>
            @if($shipment->proofOfDelivery)
                <div>
                    <strong>Proof of Delivery</strong>
                    <p style="margin:0.35rem 0 0;color:#64748b;font-size:0.95rem;">
                        Ditandatangani oleh {{ $shipment->proofOfDelivery->signed_by }}
                        pada {{ optional($shipment->proofOfDelivery->signed_at)->format('d M Y H:i') ?? '—' }}
                    </p>
                    <div class="pod-actions">
                        @if($podUrl)
                            <a href="{{ $podUrl }}" class="btn btn-primary" target="_blank" rel="noopener">Lihat bukti</a>
                        @else
                            <span style="font-size:0.85rem;color:#94a3b8;">File PoD tidak tersedia.</span>
                        @endif
                        @if($idempotentReplay)
                            <span class="pod-replay-badge" title="Aksi deliver diulang menggunakan idempotency key yang sama.">Idempotent replay</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@include('partials.realtime', [
    'shipments' => [$shipment->id],
    'shipmentMeta' => [
        $shipment->id => [
            'label' => $shipment->shipment_no ?? ('Shipment #' . $shipment->id),
            'reference' => $shipment->tracking_no,
        ],
    ],
])
@endsection
