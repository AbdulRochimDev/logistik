@extends('layouts.base', ['title' => 'Shipments', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1200px,100%); margin:0 auto; display:grid; gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .table { width:100%;border-collapse:collapse; }
    .table th,.table td { padding:0.75rem 0.6rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left;vertical-align:middle; }
    .status-pill { display:inline-flex;align-items:center;padding:0.35rem 0.75rem;border-radius:999px;font-size:0.85rem;font-weight:600; }
    .status-draft { background:rgba(148,163,184,0.25);color:#1f2937; }
    .status-allocated { background:rgba(59,130,246,0.18);color:#1d4ed8; }
    .status-dispatched { background:rgba(249,115,22,0.16);color:#c2410c; }
    .status-delivered { background:rgba(34,197,94,0.18);color:#047857; }
    .btn-inline { display:inline-flex;align-items:center;gap:0.35rem; }
    .status-message { padding:0.75rem 1rem;border-radius:12px;background:rgba(34,197,94,0.12);color:#047857;border:1px solid rgba(34,197,94,0.25); }
    .error-message { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.22); }
    .filter-shell { display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;margin-top:1.25rem; }
    .filter-shell label { font-weight:600;display:block;margin-bottom:0.35rem; }
    .filter-shell select,
    .filter-shell input { padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35); }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;font-size:1.85rem;">Daftar Shipments</h1>
                <p style="margin:0.35rem 0 0;color:#64748b;">Kelola pengiriman outbound dan statusnya.</p>
            </div>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary btn-inline">Tambah Shipment</a>
            </div>
        </div>

        <form method="GET" class="filter-shell">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="driver_id">Driver</label>
                <select id="driver_id" name="driver_id">
                    <option value="">Semua</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected((string) request('driver_id') === (string) $driver->id)>{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from">Tanggal Rencana (dari)</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div>
                <label for="date_to">Tanggal Rencana (sampai)</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <button class="btn btn-neutral" type="submit">Terapkan</button>
        </form>

        @if ($message = session('status'))
            <div class="status-message" style="margin-top:1.25rem;">{{ $message }}</div>
        @endif
        @if ($errors->any())
            <div class="error-message" style="margin-top:1.25rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <div style="overflow-x:auto;margin-top:1.5rem;">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Planned</th>
                        <th>Outbound</th>
                        <th>Status</th>
                        <th>Driver</th>
                        <th>Kendaraan</th>
                        <th>Lines (Plan/Pick/Deliv)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($shipments as $shipment)
                    @php
                        $planned = (float) $shipment->items->sum('qty_planned');
                        $picked = (float) $shipment->items->sum('qty_picked');
                        $delivered = (float) $shipment->items->sum('qty_delivered');
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                <strong>{{ $shipment->shipment_no ?? ('SHP-' . str_pad($shipment->id, 4, '0', STR_PAD_LEFT)) }}</strong>
                                <a href="{{ route('admin.shipments.show', $shipment) }}" style="font-size:0.85rem;color:#0ea5e9;text-decoration:none;">Detail</a>
                            </div>
                        </td>
                        <td>{{ optional($shipment->planned_at)->format('d M Y H:i') ?? '—' }}</td>
                        <td>
                            @if($shipment->outboundShipment)
                                {{ $shipment->outboundShipment->salesOrder?->so_no ?? 'Outbound #'.$shipment->outboundShipment->id }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $shipment->status }}">{{ ucfirst($shipment->status) }}</span>
                        </td>
                        <td>{{ $shipment->driver?->name ?? 'Belum di-assign' }}</td>
                        <td>{{ $shipment->vehicle?->plate_no ?? 'Belum di-assign' }}</td>
                        <td>{{ number_format($planned, 2) }} / {{ number_format($picked, 2) }} / {{ number_format($delivered, 2) }}</td>
                        <td>
                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                <a href="{{ route('admin.shipments.edit', $shipment) }}" class="btn btn-neutral btn-inline">Assign/Edit</a>
                                @if(in_array($shipment->status, ['draft','allocated'], true))
                                    <form method="POST" action="{{ route('admin.shipments.dispatch', $shipment) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-inline" type="submit">Dispatch</button>
                                    </form>
                                @endif
                                @if($shipment->status === 'dispatched')
                                    <form method="POST" action="{{ route('admin.shipments.deliver', $shipment) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-inline" type="submit">Mark Delivered</button>
                                    </form>
                                @elseif($shipment->status === 'delivered')
                                    <span class="status-pill status-delivered">Delivered</span>
                                @endif
                                @if($shipment->status === 'draft')
                                    <form method="POST" action="{{ route('admin.shipments.destroy', $shipment) }}" onsubmit="return confirm('Hapus shipment ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-neutral btn-inline" style="border:1px solid rgba(239,68,68,0.35);color:#ef4444;" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Belum ada shipment.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            {{ $shipments->links() }}
        </div>
    </div>
</div>
@endsection
