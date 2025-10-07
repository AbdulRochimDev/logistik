@extends('layouts.base', [
    'title' => 'Panel Admin Gudang · Monitoring Logistik Gudang',
    'mainClass' => 'layout-wide',
])

@push('styles')
<style>
    .dashboard-shell {
        width: min(1180px, 100%);
        margin: 0 auto;
        display: grid;
        gap: 2.5rem;
    }

    .dashboard-hero {
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(45, 212, 191, 0.12));
        border: 1px solid rgba(14, 165, 233, 0.25);
        border-radius: 22px;
        padding: 2.5rem;
        display: grid;
        gap: 1.5rem;
    }

    .dashboard-hero h1 {
        margin: 0;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
    }

    .stat-pill-group {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .stat-pill {
        padding: 0.85rem 1.4rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(148, 163, 184, 0.35);
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-weight: 600;
        color: #0f172a;
    }

    .metric-grid {
        display: grid;
        gap: 1.35rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .metric-card {
        background: white;
        border-radius: 18px;
        padding: 1.8rem;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        display: grid;
        gap: 0.35rem;
    }

    .metric-card h3 {
        margin: 0;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }

    .metric-card strong {
        font-size: 1.85rem;
        color: #0f172a;
    }

    .metric-card span {
        color: #64748b;
        font-size: 0.95rem;
    }

    .grid-columns {
        display: grid;
        gap: 1.5rem;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }

    .panel-card {
        background: white;
        border-radius: 18px;
        padding: 1.8rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .panel-card h2 {
        margin: 0 0 1.1rem;
        font-size: 1.3rem;
    }

    .warehouse-table {
        width: 100%;
        border-collapse: collapse;
    }

    .warehouse-table th,
    .warehouse-table td {
        padding: 0.65rem 0.5rem;
        text-align: left;
        border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        font-size: 0.95rem;
    }

    .movement-feed {
        display: grid;
        gap: 1rem;
        max-height: 280px;
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .movement-item {
        padding: 1rem 1.1rem;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: rgba(248, 250, 252, 0.85);
        display: grid;
        gap: 0.4rem;
    }

    .movement-item strong {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.98rem;
    }

    .quick-links {
        display: grid;
        gap: 0.85rem;
    }

    .quick-links a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 1.1rem;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        text-decoration: none;
        color: #0f172a;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.9);
    }

    .quick-links a span:last-child {
        color: #0ea5e9;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .dashboard-hero {
            padding: 1.8rem;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-shell">
    <section class="dashboard-hero">
        <h1>Panel Admin Gudang</h1>
        <p>Ikuti pergerakan inbound, outbound, dan stok secara realtime. Semua mutasi tetap melalui StockService untuk menjaga akurasi kuantitas.</p>
        <div class="stat-pill-group">
            <span class="stat-pill">Total PO Aktif: {{ $openPurchaseOrders }}</span>
            <span class="stat-pill">Lokasi Terdaftar: {{ $warehouseBreakdown->sum('locations_count') }}</span>
            <a class="stat-pill" href="{{ route('admin.purchase-orders.index') }}" style="text-decoration:none;">
                Kelola PO →
            </a>
        </div>
    </section>

    <section class="metric-grid">
        <div class="metric-card">
            <h3>On Hand</h3>
            <strong>{{ number_format((float) ($totals->qty_on_hand ?? 0), 0, ',', '.') }}</strong>
            <span>Qty tersedia fisik di seluruh gudang</span>
        </div>
        <div class="metric-card">
            <h3>Allocated</h3>
            <strong>{{ number_format((float) ($totals->qty_allocated ?? 0), 0, ',', '.') }}</strong>
            <span>Sudah dikunci untuk outbound / pick list</span>
        </div>
        <div class="metric-card">
            <h3>Available</h3>
            <strong>{{ number_format((float) ($totals->qty_available ?? 0), 0, ',', '.') }}</strong>
            <span>Dapat digunakan untuk alokasi baru</span>
        </div>
    </section>

    <section class="grid-columns">
        <article class="panel-card">
            <h2>Performa Gudang</h2>
            @include('dashboard.partials.warehouse-performance', ['warehouseBreakdown' => $warehouseBreakdown])
        </article>

        <article class="panel-card">
            <h2>Aktivitas Stok Terbaru</h2>
            <div class="movement-feed">
                @forelse($recentMovements as $movement)
                    <div class="movement-item">
                        <strong>
                            <span>{{ $movement['type'] }}</span>
                            <span>{{ number_format((float) $movement['quantity'], 0, ',', '.') }}</span>
                        </strong>
                        <span>SKU: {{ $movement['item'] }} · {{ $movement['warehouse'] ?? 'Gudang' }}</span>
                        <span style="color:#64748b;font-size:0.85rem;">{{ $movement['timestamp'] ?? '—' }}</span>
                        @if(! empty($movement['remarks']))
                            <span style="color:#475569;font-size:0.9rem;">"{{ $movement['remarks'] }}"</span>
                        @endif
                    </div>
                @empty
                    <p>Belum ada movement tercatat.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="panel-card">
        <h2>Quick Actions</h2>
        <div class="quick-links">
            <a href="{{ route('admin.grn.create') }}">
                <span>Posting GRN Baru</span>
                <span>→</span>
            </a>
            <a href="{{ route('admin.locations.index') }}">
                <span>Kelola Lokasi Gudang</span>
                <span>→</span>
            </a>
            <a href="{{ route('admin.purchase-orders.index') }}">
                <span>Kelola Purchase Order</span>
                <span>→</span>
            </a>
        </div>
    </section>
</div>
@endsection
