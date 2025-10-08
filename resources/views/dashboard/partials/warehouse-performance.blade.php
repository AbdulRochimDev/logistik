<table class="warehouse-table">
    <thead>
        <tr>
            <th>Gudang</th>
            <th>On Hand</th>
            <th>Allocated</th>
            <th>Available</th>
            <th>Picked Today</th>
            <th>Delivered Today</th>
        </tr>
    </thead>
    <tbody>
    @forelse($warehouseBreakdown as $warehouse)
        @php
            $available = ($warehouse->qty_on_hand ?? 0) - ($warehouse->qty_allocated ?? 0);
        @endphp
        <tr>
            <td>{{ $warehouse->name }}</td>
            <td>{{ number_format((float) ($warehouse->qty_on_hand ?? 0), 0, ',', '.') }}</td>
            <td>{{ number_format((float) ($warehouse->qty_allocated ?? 0), 0, ',', '.') }}</td>
            <td>{{ number_format((float) $available, 0, ',', '.') }}</td>
            <td>{{ number_format((float) ($warehouse->picked_today ?? 0), 0, ',', '.') }}</td>
            <td>{{ number_format((float) ($warehouse->delivered_today ?? 0), 0, ',', '.') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6">Belum ada data stok gudang.</td>
        </tr>
    @endforelse
    </tbody>
</table>
