# Workflow Overview

## Inbound (GRN Posting)
- **Trigger:** Admin gudang mengirim POST `/api/admin/inbound/grn` dengan daftar line hasil penerimaan.
- **Validasi:** `PostGrnRequest` mengecek setiap line (qty >0, lokasi valid, lot wajib untuk item lot-tracked).
- **Orkestrasi:** `PostGrnController` membangun `PostGrnData` + `PostGrnLineData`, menarik `X-Idempotency-Key` (jika ada), lalu memanggil `GrnService::post()`.
- **Transaksi:** `GrnService` men-lock inbound shipment & PO item, menghitung/menetapkan `external_idempotency_key`, membuat/ambil GRN header & line (draft→posted), membuat lot bila perlu, serta memanggil `StockService::move()` tipe `inbound_putaway` per line.
- **Stok:** `StockService` satu pintu perubahan saldo (`stocks` & `stock_movements`) dengan idempotensi `ref_type/ref_id/type`.
- **Hasil:** API mengembalikan ringkasan GRN (ID, nomor, kunci idempoten, lines_processed/lines_skipped, movements) dan dapat dipanggil ulang dengan payload identik tanpa menggandakan stok.

## Outbound (Allocate → Pick → Dispatch → Deliver)
- **Trigger:** Admin gudang memicu POST `/api/admin/outbound/allocate` per line SO untuk mengunci stok lokasi (wajib lot_no bila item lot-tracked).
- **Validasi:** `AllocateRequest` + `OutboundService::allocate()` memastikan lokasi se-gudang, outstanding SO cukup, stok tersedia, dan idempotensi berdasarkan key.
- **Pick:** `POST /api/admin/outbound/pick/complete` membentuk `PickLineData` dan memanggil `OutboundService::completePick()` → `StockService::move('pick', ...)` mengurangi `qty_on_hand` sambil menjaga `qty_allocated` hingga PoD.
- **Dispatch:** `POST /api/admin/outbound/shipment/dispatch` memperbarui carrier/timestamp pada shipment + outbound.
- **Deliver:** Driver/admin memanggil `POST /api/admin/outbound/shipment/deliver` membawa `ShipmentPodData`; layanan membuat PoD (idempotensi key) dan menjalankan `StockService::move('deliver', ...)` untuk melepas alokasi residual.

## Handheld Scan Flow
- **Endpoint:** `/api/scan` menerima payload dari perangkat (sku, qty, direction, location, ts, device_id, lot_no?).
- **Idempoten:** Header opsional `X-Idempotency-Key`; jika kosong sistem hash `SCAN|device|ts|payload` dan menurunkannya ke `StockService::move()` (ref_type `SCAN`).
- **Mapping:** `direction=in` → `inbound_putaway` (to location); `direction=out` → `pick` (from location). Lot-tracked item wajib lot_no.
- **Hasil:** Response mengembalikan movement_id, tipe, dan flag `applied` (false pada replay) tanpa menggandakan saldo.
- **Realtime:** Setelah commit, `StockUpdated` disiarkan ke channel `private-wms.scan.{WAREHOUSE}` atau `private-wms.inbound.grn.{WAREHOUSE}` dan dapat ditangkap frontend.

```
Scanner ──▶ /api/scan (Sanctum)
             │
             ├──▶ StockService::move() ──▶ TiDB (TLS)
             │                               │
             └──▶ DB::afterCommit ──▶ StockUpdated (Ably)
                                         │
                                         └──▶ Echo/Ably JS subscriber render dashboard
```

## Operational Checks
- **TLS Healthcheck:** `php artisan db:ping -v` sebelum deploy memastikan host TiDB, port 4000, dan sertifikat CA terbaca. Kegagalan akan menampilkan path CA yang harus diperbaiki.
- **Admin Access:** `php artisan db:seed --class=AdminUserSeeder --force` menyiapkan `admin@example.com` dengan password dari `ADMIN_PASSWORD` (default `ChangeMe!123`).
- **Auth Smoke Test:** Masuk via `/login` menggunakan admin tersebut; dashboard admin menegaskan role `admin_gudang` aktif.

## Admin CRUD & Safeguards
```
Supplier → PurchaseOrder (header + lines) ──┐
                                          │
                                          ▼
InboundShipment → Quick GRN (admin/grn) → GrnService → StockService::move
```
- Semua form admin berada di prefix `admin/*` (middleware `auth`, `role:admin_gudang`). Store/update PO & GRN dibungkus transaksi DB.
- Hapus lokasi dicegah bila stok (`qty_on_hand`/`qty_allocated`) masih > 0; pesan error dikembalikan ke UI.
