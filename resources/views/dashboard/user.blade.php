@extends('layouts.base', [
    'title' => 'Panel Pengguna · Monitoring Logistik Gudang',
    'mainClass' => 'layout-wide',
])

@push('styles')
<style>
    .user-dashboard {
        width: min(1100px, 100%);
        margin: 0 auto;
        display: grid;
        gap: 2.25rem;
    }

    .user-header {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(139, 92, 246, 0.15));
        border: 1px solid rgba(59, 130, 246, 0.25);
        border-radius: 20px;
        padding: 2.25rem;
        display: grid;
        gap: 1.35rem;
    }

    .user-header h1 {
        margin: 0;
        font-size: clamp(2rem, 3vw, 2.4rem);
    }

    .user-header p {
        margin: 0;
        color: #475569;
        max-width: 65ch;
    }

    .task-grid {
        display: grid;
        gap: 1.2rem;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }

    .task-card {
        background: white;
        border-radius: 18px;
        padding: 1.6rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        display: grid;
        gap: 0.45rem;
    }

    .task-card h3 {
        margin: 0;
        font-size: 1.15rem;
    }

    .task-card p {
        margin: 0;
        color: #64748b;
        font-size: 0.95rem;
    }

    .task-card a {
        margin-top: 0.5rem;
        color: #0ea5e9;
        font-weight: 600;
        text-decoration: none;
    }

    .table-card {
        background: white;
        border-radius: 18px;
        padding: 1.8rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .table-card h2 {
        margin: 0 0 1rem;
        font-size: 1.3rem;
    }

    table.stock-table {
        width: 100%;
        border-collapse: collapse;
    }

    table.stock-table th,
    table.stock-table td {
        padding: 0.6rem 0.5rem;
        text-align: left;
        border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        font-size: 0.95rem;
    }

    .movement-list {
        display: grid;
        gap: 0.85rem;
    }

    .movement-line {
        padding: 0.8rem 1rem;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: rgba(248, 250, 252, 0.85);
        display: grid;
        gap: 0.25rem;
    }

    .movement-line strong {
        display: flex;
        justify-content: space-between;
        font-size: 0.95rem;
    }

    .movement-line span {
        color: #475569;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
@endphp
<div class="user-dashboard">
    <section class="user-header">
        <h1>Selamat datang, {{ $user?->name ?? 'Rekan Gudang' }}</h1>
        <p>Lanjutkan tugas hari ini: pastikan inbound tersimpan rapi, outbound akurat, dan gunakan tombol Scan untuk pergerakan cepat. Semua perubahan stok otomatis tercatat di StockService.</p>
    </section>

    <section class="task-grid">
        <article class="task-card">
            <h3>Proses Putaway</h3>
            <p>Gunakan aplikasi mobile untuk scan lokasi dan lot sebelum mengonfirmasi putaway.</p>
            <a href="#">Lihat panduan →</a>
        </article>
        <article class="task-card">
            <h3>Alur Picking</h3>
            <p>Ikuti urutan pick list, periksa kuantitas, dan mark complete agar stok berkurang otomatis.</p>
            <a href="#">Mulai picking →</a>
        </article>
        <article class="task-card">
            <h3>Laporan Cepat</h3>
            <p>Unduh ringkasan stok per lokasi atau cek histori movement dari table di bawah.</p>
            <a href="#">Download laporan →</a>
        </article>
    </section>

    <section class="table-card">
        <h2>Stok Teratas</h2>
        <table class="stock-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Lokasi</th>
                    <th>On Hand</th>
                    <th>Allocated</th>
                </tr>
            </thead>
            <tbody>
            @forelse($topStocks as $stock)
                <tr>
                    <td>{{ $stock->item?->sku ?? 'SKU?' }}</td>
                    <td>{{ $stock->location?->code ?? '-' }}</td>
                    <td>{{ number_format((float) $stock->qty_on_hand, 0, ',', '.') }}</td>
                    <td>{{ number_format((float) $stock->qty_allocated, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada stok tercatat.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="table-card">
        <h2>Aktivitas Terbaru</h2>
        <div class="movement-list">
            @forelse($recentMovements as $movement)
                <div class="movement-line">
                    <strong>
                        <span>{{ $movement['type'] }}</span>
                        <span>{{ number_format((float) $movement['quantity'], 0, ',', '.') }}</span>
                    </strong>
                    <span>SKU: {{ $movement['item'] }} • {{ $movement['timestamp'] ?? '—' }}</span>
                </div>
            @empty
                <p>Belum ada aktivitas hari ini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
