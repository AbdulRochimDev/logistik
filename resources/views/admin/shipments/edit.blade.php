@extends('layouts.base', ['title' => 'Edit Shipment', 'mainClass' => 'layout-wide'])

@push('styles')
<style>
    .page-shell { width:min(1100px,100%);margin:0 auto;display:grid;gap:1.5rem; }
    .card { background:white;border-radius:18px;border:1px solid rgba(148,163,184,0.25);box-shadow:0 12px 24px rgba(15,23,42,0.08);padding:1.8rem; }
    .page-title { margin:0;font-size:1.85rem; }
    .alert-error { padding:0.85rem 1.1rem;border-radius:12px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#b91c1c; }
    .alert-info { padding:0.85rem 1.1rem;border-radius:12px;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);color:#1d4ed8; }
</style>
@endpush

@section('content')
<div class="page-shell">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="page-title">Edit Shipment {{ $shipment->shipment_no ?? ('SHP-' . str_pad($shipment->id, 4, '0', STR_PAD_LEFT)) }}</h1>
                <p style="margin:0.35rem 0 0;color:#64748b;">Perbarui informasi shipment selama masih draft/allocated.</p>
            </div>
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-neutral">Kembali ke Detail</a>
        </div>

        @if($errors->has('edit'))
            <div class="alert-error" style="margin-top:1rem;">{{ $errors->first('edit') }}</div>
        @elseif(! in_array($shipment->status, ['draft','allocated'], true))
            <div class="alert-info" style="margin-top:1rem;">Shipment sudah {{ $shipment->status }}, perubahan mungkin tidak disimpan.</div>
        @endif
    </div>

    @include('admin.shipments._form', ['shipment' => $shipment])
</div>
@endsection
