@extends('layouts.base', ['title' => 'Kendaraan', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%); margin:0 auto; display:grid; gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .table { width:100%;border-collapse:collapse; }
    .table th,.table td { padding:0.75rem 0.5rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left; }
    .status-pill { display:inline-flex;align-items:center;padding:0.35rem 0.75rem;border-radius:999px;font-size:0.85rem;font-weight:600; }
    .status-active { background:rgba(59,130,246,0.2);color:#1d4ed8; }
    .status-inactive { background:rgba(148,163,184,0.25);color:#1f2937; }
    .status-message { padding:0.75rem 1rem;border-radius:12px;background:rgba(34,197,94,0.12);color:#047857;border:1px solid rgba(34,197,94,0.25); }
    .error-message { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.25); }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <h1 style="margin:0;font-size:1.8rem;">Daftar Kendaraan</h1>
            <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary" style="text-decoration:none;">Tambah Kendaraan</a>
        </div>

        <form method="GET" style="margin-top:1.25rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label for="status" style="font-weight:600;display:block;margin-bottom:0.35rem;">Status</label>
                <select id="status" name="status" style="padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                    <option value="">Semua</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <button class="btn btn-neutral" type="submit">Filter</button>
        </form>

        @if ($message = session('status'))
            <div class="status-message" style="margin-top:1.25rem;">{{ $message }}</div>
        @endif

        @if ($errors->has('delete'))
            <div class="error-message" style="margin-top:1.25rem;">{{ $errors->first('delete') }}</div>
        @endif

        <div style="margin-top:1.5rem;overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Plat Nomor</th>
                        <th>Tipe</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th>Shipment Aktif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td>{{ $vehicle->plate_no }}</td>
                        <td>{{ $vehicle->type ?? '-' }}</td>
                        <td>{{ $vehicle->capacity ? number_format($vehicle->capacity, 1) : '-' }}</td>
                        <td><span class="status-pill status-{{ $vehicle->status }}">{{ ucfirst($vehicle->status) }}</span></td>
                        <td>{{ $vehicle->shipments_count }}</td>
                        <td style="text-align:right;display:flex;gap:0.75rem;justify-content:flex-end;">
                            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-neutral">Edit</a>
                            <form action="{{ route('admin.vehicles.destroy', $vehicle) }}" method="POST" onsubmit="return confirm('Hapus kendaraan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-neutral" style="color:#ef4444;border:1px solid rgba(239,68,68,0.35);">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada kendaraan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            {{ $vehicles->links() }}
        </div>
    </div>
</div>
@endsection
