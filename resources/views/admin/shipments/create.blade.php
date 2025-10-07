@extends('layouts.base', ['title' => 'Tambah Shipment', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%);margin:0 auto;display:grid;gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .page-title { margin:0;font-size:1.85rem; }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="page-title">Buat Shipment Baru</h1>
                <p style="margin:0.35rem 0 0;color:#64748b;">Susun rencana pengiriman, assign driver & kendaraan.</p>
            </div>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-neutral">Kembali</a>
        </div>
    </div>

    @include('admin.shipments._form')
</div>
@endsection
