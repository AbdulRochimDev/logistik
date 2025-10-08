@extends('layouts.base', [
    'title' => 'Monitor Pergerakan Stok · Logistik',
    'mainClass' => 'layout-wide',
])

@push('styles')
<style>
    .monitor-shell {
        width: min(1180px, 100%);
        margin: 0 auto;
        display: grid;
        gap: 2rem;
    }

    .monitor-hero {
        background: linear-gradient(130deg, rgba(59, 130, 246, 0.18), rgba(16, 185, 129, 0.15));
        border-radius: 22px;
        border: 1px solid rgba(59, 130, 246, 0.3);
        padding: 2.4rem;
        display: grid;
        gap: 1rem;
    }

    .monitor-hero h1 {
        margin: 0;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
    }

    .matrix-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    }

    .matrix-card,
    .audit-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        padding: 1.6rem;
        display: grid;
        gap: 0.9rem;
    }

    .matrix-card h2 {
        margin: 0;
        font-size: 1.1rem;
    }

    .rule-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 0.75rem;
        border-radius: 12px;
        background: rgba(241, 245, 249, 0.7);
        font-size: 0.95rem;
    }

    .rule-row span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .summary-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }

    .summary-grid table {
        width: 100%;
        border-collapse: collapse;
    }

    .summary-grid th,
    .summary-grid td {
        text-align: left;
        padding: 0.6rem 0.4rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        font-size: 0.95rem;
    }

    .audit-feed {
        display: grid;
        gap: 0.85rem;
        max-height: 360px;
        overflow-y: auto;
        padding-right: 0.4rem;
    }

    .audit-item {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        padding: 1rem 1.1rem;
        background: rgba(248, 250, 252, 0.95);
        display: grid;
        gap: 0.45rem;
        font-size: 0.95rem;
    }

    .audit-item strong {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.1);
        color: #1d4ed8;
    }

    .badge[data-context="scan"] {
        background: rgba(244, 114, 182, 0.12);
        color: #be185d;
    }
</style>
@endpush

@section('content')
<div class="monitor-shell">
    <section class="monitor-hero">
        <h1>Monitor Pergerakan Stok</h1>
        <p>Transparansi penuh terhadap setiap mutasi stok dari StockService. Gunakan matriks di bawah untuk memahami dampak
            masing-masing movement dan audit trail untuk investigasi cepat.</p>
        <div>
            <a href="{{ route('admin.dashboard') }}" style="color:#0f172a;font-weight:600;text-decoration:none;">
                ← Kembali ke Dashboard Admin
            </a>
        </div>
    </section>

    <section class="matrix-grid">
        @foreach($matrix as $movement)
            <div class="matrix-card">
                <h2>{{ $movement['type'] }}</h2>
                @foreach($movement['rules'] as $rule)
                    <div class="rule-row">
                        <span><strong>{{ strtoupper($rule['direction']) }}</strong> lokasi</span>
                        <span>On-hand: {{ number_format($rule['on_hand'], 2) }}</span>
                        <span>Allocated: {{ number_format($rule['allocated'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>

    <section class="summary-grid">
        <div class="audit-card">
            <h2>Ringkasan Movement</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipe</th>
                        <th>Frekuensi</th>
                        <th>Total Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($typeSummary as $summary)
                        <tr>
                            <td>{{ $summary['type'] }}</td>
                            <td>{{ number_format($summary['count']) }}</td>
                            <td>{{ number_format($summary['total_qty'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">Belum ada histori movement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="audit-card">
            <h2>Audit Trail Terbaru</h2>
            <div class="audit-feed">
                @forelse($recentAudits as $audit)
                    <div class="audit-item">
                        <strong>
                            <span>{{ \Illuminate\Support\Str::headline($audit->type) }} · {{ $audit->sku ?? 'SKU?' }}</span>
                            <span class="badge" data-context="{{ $audit->context }}">{{ strtoupper($audit->context) }}</span>
                        </strong>
                        <span>Warehouse {{ $audit->warehouse_code }} @if($audit->location_code) · Lokasi {{ $audit->location_code }} @endif</span>
                        <span>Qty: {{ number_format($audit->quantity, 2) }} | On-hand: {{ number_format($audit->qty_on_hand, 2) }} | Allocated: {{ number_format($audit->qty_allocated, 2) }}</span>
                        <span>Ref: {{ $audit->ref_type }} · {{ $audit->ref_id }}</span>
                        <span>Waktu: {{ optional($audit->moved_at)->format('d M Y H:i:s') }}</span>
                    </div>
                @empty
                    <p>Belum ada data audit dari StockService.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
