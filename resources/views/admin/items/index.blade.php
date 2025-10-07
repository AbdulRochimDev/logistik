@extends('layouts.base', ['title' => 'Items', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%); margin:0 auto; display:grid; gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .table { width:100%;border-collapse:collapse; }
    .table th,.table td { padding:0.75rem 0.5rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left; }
    .btn-link { color:#0ea5e9;text-decoration:none;font-weight:600; }
    .status { padding:0.75rem 1rem;border-radius:12px;background:rgba(34,197,94,0.12);color:#047857;border:1px solid rgba(34,197,94,0.25); }
    .error { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.25); }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <h1 style="margin:0;font-size:1.8rem;">Master Item</h1>
            <a href="{{ route('admin.items.create') }}" class="btn btn-primary" style="text-decoration:none;">Tambah Item</a>
        </div>

        <form method="GET" style="margin-top:1.25rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:220px;">
                <label for="q" style="font-weight:600;display:block;margin-bottom:0.35rem;">Cari</label>
                <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="SKU / Nama" style="width:100%;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            </div>
            <div>
                <label for="is_lot_tracked" style="font-weight:600;display:block;margin-bottom:0.35rem;">Lot Tracked</label>
                <select id="is_lot_tracked" name="is_lot_tracked" style="width:100%;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('is_lot_tracked') === '1')>Ya</option>
                    <option value="0" @selected(request('is_lot_tracked') === '0')>Tidak</option>
                </select>
            </div>
            <button type="submit" class="btn btn-neutral">Filter</button>
        </form>

        @if ($message = session('status'))
            <div class="status" style="margin-top:1.25rem;">{{ $message }}</div>
        @endif
        @if ($errors->has('delete'))
            <div class="error" style="margin-top:1.25rem;">{{ $errors->first('delete') }}</div>
        @endif

        <div style="margin-top:1.5rem;overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama</th>
                        <th>UOM</th>
                        <th>Lot Tracked</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->sku }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->default_uom }}</td>
                        <td>{{ $item->is_lot_tracked ? 'Ya' : 'Tidak' }}</td>
                        <td style="text-align:right;display:flex;gap:0.75rem;justify-content:flex-end;">
                            <a href="{{ route('admin.items.edit', $item) }}" class="btn-link">Edit</a>
                            <form action="{{ route('admin.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus item ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-link" style="color:#ef4444;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada item.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
