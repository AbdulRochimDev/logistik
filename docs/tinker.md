# Laravel Tinker Cheat Sheet — Monitoring Logistik Gudang

Gunakan `php artisan tinker` pada environment lokal (jangan pada produksi). Contoh snippet:

## Cek Stok per Item & Lokasi
```php
use App\Domain\Inventory\Models\Stock;

Stock::query()
    ->where('item_id', 123)
    ->where('warehouse_id', 1)
    ->get(['location_id', 'qty_on_hand', 'qty_allocated', 'qty_available']);
```

## Simulasi Perpindahan Stok
```php
use App\Domain\Inventory\Services\StockService;

app(StockService::class)->move(
    'inbound_putaway',
    warehouseId: 1,
    itemId: 123,
    lotId: null,
    fromLocationId: null,
    toLocationId: 45,
    qty: 5,
    uom: 'PCS',
    refType: 'TINKER',
    refId: uniqid('tinker-'),
    actorUserId: auth()->id(),
    movedAt: now(),
    remarks: 'Debugging session'
);
```

## Cek Movement Terbaru
```php
use App\Domain\Inventory\Models\StockMovement;

StockMovement::query()
    ->latest('moved_at')
    ->limit(5)
    ->get(['type', 'warehouse_id', 'item_id', 'from_location_id', 'to_location_id', 'quantity', 'ref_type', 'ref_id']);
```

> Catatan: Selalu gunakan referensi `ref_type/ref_id` unik saat eksperimen agar idempotensi StockService tidak menolak eksekusi uji coba Anda.
