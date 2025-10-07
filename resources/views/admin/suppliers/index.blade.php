@extends('layouts.base', ['title' => 'Supplier', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%); margin:0 auto; display:grid; gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .table { width:100%;border-collapse:collapse; }
    .table th,.table td { padding:0.75rem 0.5rem;border-bottom:1px solid rgba(148,163,184,0.25);text-align:left; }
    .status { padding:0.75rem 1rem;border-radius:12px;background:rgba(34,197,94,0.12);color:#047857;border:1px solid rgba(34,197,94,0.25); }
    .error { padding:0.75rem 1rem;border-radius:12px;background:rgba(239,68,68,0.08);color:#b91c1c;border:1px solid rgba(239,68,68,0.25); }
    .btn-link { color:#0ea5e9;text-decoration:none;font-weight:600; }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <h1 style="margin:0;font-size:1.8rem;">Daftar Supplier</h1>
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary" style="text-decoration:none;">Tambah Supplier</a>
        </div>

        <form method="GET" style="margin-top:1.25rem;display:flex;gap:1rem;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label for="q" style="font-weight:600;display:block;margin-bottom:0.35rem;">Cari</label>
                <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Kode / Nama" style="width:100%;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
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
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->code }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_name }}</td>
                        <td>{{ $supplier->email }}</td>
                        <td>{{ $supplier->phone }}</td>
                        <td style="text-align:right;display:flex;gap:0.75rem;justify-content:flex-end;">
                            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn-link">Edit</a>
                            <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Hapus supplier ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-link" style="color:#ef4444;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada supplier.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>
@endsection
