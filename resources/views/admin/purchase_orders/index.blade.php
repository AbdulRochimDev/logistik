@extends('layouts.base', ['title' => 'Purchase Orders', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1180px,100%); margin:0 auto; display:grid; gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .table { width:100%;border-collapse:collapse; }
    .table th,.table td { padding:0.75rem 0.5rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left; }
    .status { padding:0.75rem 1rem;border-radius:12px;background:rgba(34,197,94,0.12);color:#047857;border:1px solid rgba(34,197,94,0.25); }
    .error { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.25); }
    .badge { display:inline-flex;align-items:center;padding:0.35rem 0.75rem;border-radius:999px;font-size:0.85rem;font-weight:600; }
    .badge-draft { background:rgba(234,179,8,0.15);color:#92400e; }
    .badge-approved { background:rgba(34,197,94,0.15);color:#065f46; }
    .badge-closed { background:rgba(148,163,184,0.2);color:#1f2937; }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <h1 style="margin:0;font-size:1.8rem;">Purchase Orders</h1>
            <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary" style="text-decoration:none;">Buat PO</a>
        </div>

        <form method="GET" style="margin-top:1.25rem;display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
            <div>
                <label for="q" style="font-weight:600;display:block;margin-bottom:0.35rem;">Cari</label>
                <input id="q" name="q" value="{{ request('q') }}" placeholder="PO No" style="width:100%;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            </div>
            <div>
                <label for="status" style="font-weight:600;display:block;margin-bottom:0.35rem;">Status</label>
                <select id="status" name="status" style="width:100%;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                    <option value="">Semua</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="supplier_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Supplier</label>
                <select id="supplier_id" name="supplier_id" style="width:100%;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                    <option value="">Semua</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:end;">
                <button class="btn btn-neutral" type="submit">Filter</button>
            </div>
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
                        <th>PO No</th>
                        <th>Supplier</th>
                        <th>Gudang</th>
                        <th>Status</th>
                        <th>ETA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($purchaseOrders as $po)
                    <tr>
                        <td>{{ $po->po_no }}</td>
                        <td>{{ $po->supplier?->name }}</td>
                        <td>{{ $po->warehouse?->name }}</td>
                        <td>
                            <span class="badge badge-{{ $po->status }}">{{ ucfirst($po->status) }}</span>
                        </td>
                        <td>{{ $po->eta?->format('d M Y') }}</td>
                        <td style="text-align:right;display:flex;gap:0.75rem;justify-content:flex-end;">
                            <a href="{{ route('admin.purchase-orders.edit', $po) }}" class="btn-link">Edit</a>
                            <form action="{{ route('admin.purchase-orders.destroy', $po) }}" method="POST" onsubmit="return confirm('Hapus PO ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-link" style="color:#ef4444;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada purchase order.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            {{ $purchaseOrders->links() }}
        </div>
    </div>
</div>
@endsection
