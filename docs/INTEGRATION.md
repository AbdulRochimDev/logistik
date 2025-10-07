# Integration Guide

## QA & Tooling
- Jalankan `composer qa` (Pint → PHPStan → Pest) sebelum rilis. PHPStan memakai konfigurasi `phpstan.neon` dengan level 5 dan lintas path (`app`, `database/seeders`, `routes`).
- Konfigurasi tambahan tersedia di `config/wms.php` untuk password default, akun demo, dan flag SSL database; gunakan `config()` ketimbang membaca `.env` langsung di runtime/seed/test.
- Fallback Sanctum (tanpa paket `laravel/sanctum`) kini dikemas melalui adaptor `App\Support\Auth\InteractsWithApiTokens` yang memilih trait bawaan Sanctum bila tersedia, atau `HasApiTokensFallback` jika tidak.
- Guard `sanctum` di `config/auth.php` otomatis memakai driver `sanctum` ketika paket tersedia dan turun ke `session` saat fallback digunakan.

## TiDB TLS Setup
- Simpan sertifikat root TiDB di dalam repo, contoh: `storage/TIDB/isrgrootx1supl.pem`. Path relatif otomatis diubah menjadi absolut oleh konfigurasi (`config/database.php` akan memanggil `base_path()`), jadi pastikan file berada di lokasi yang sama di mesin lokal, CI, dan produksi.
- Atur `.env` dengan variabel berikut:

```
DB_CONNECTION=mysql
DB_HOST=gateway01.ap-northeast-1.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=test
DB_USERNAME=78a9t1mjCDR2quY.root
DB_PASSWORD=********
DB_SSL=true
DB_CA_PATH=storage/TIDB/isrgrootx1supl.pem
# Optional hardening
DB_SSL_VERIFY_SERVER_CERT=false

BROADCAST_DRIVER=ably
ABLY_KEY=your-ably-key
```

- Jalankan verifikasi:
  1. `php artisan config:clear`
  2. `php artisan db:ping -v` → menampilkan host:port dan status SSL (sertifikat wajib ditemukan). Pesan gagal akan menyebutkan path CA sehingga mudah diperbaiki.
  3. `php artisan migrate --force`
  4. `php artisan db:seed --class=AdminUserSeeder --force`

## Inbound / GRN API
- **Endpoint:** `POST /api/admin/inbound/grn`
- **Headers:**
  - `Authorization: Bearer <token>`
  - `X-Idempotency-Key: <optional custom key>` — provide to control retries; otherwise the service derives a deterministic key `GRN|<supplier>|<inbound>|sha256(lines)`.
- **Request Body (JSON):**

```json
{
  "inbound_shipment_id": 123,
  "received_at": "2025-10-07T09:30:00+07:00",
  "notes": "Dock 1 unloading",
  "lines": [
    {
      "po_item_id": 991,
      "item_id": 501,
      "qty": 10,
      "to_location_id": 301,
      "lot_no": "LOT-XYZ-001"
    },
    {
      "po_item_id": 992,
      "item_id": 502,
      "qty": 5,
      "to_location_id": 302
    }
  ]
}
```

- **Response (201 Created on first success / 200 OK on replay):**

```json
{
  "data": {
    "grn_id": 441,
    "grn_no": "GRN-20251007-ABCD",
    "external_idempotency_key": "KEY-123" ,
    "lines_processed": 2,
    "lines_skipped": 0,
    "movements": [
      { "id": 1001, "stock_id": 7001, "quantity": 10, "location_id": 301 },
      { "id": 1002, "stock_id": 7002, "quantity": 5, "location_id": 302 }
    ]
  }
}
```

- **Retry behaviour:**
  1. Re-send the identical payload with the same `X-Idempotency-Key` (or no header) to recover from network errors.
  2. The service reuses the stored `external_idempotency_key` and skips already processed lines, leaving stock unchanged.
  3. Response flags `lines_processed=0` and `lines_skipped=<n>` on idempotent replay.

- **Validation errors (422):**
  - `lines.*.lot_no` missing for lot-tracked items.
  - `lines.*.to_location_id` not in the same warehouse as the inbound shipment.
  - Quantity ≤ 0 or referenced entities not found.

- **Conflict errors (409):**
  - Supplied `X-Idempotency-Key` already used by another inbound shipment.
  - GRN header supplied does not belong to the inbound shipment.

Ensure Laravel scheduler (or job processors) run with the same database and cache so that idempotency keys are respected across replicas.

## Outbound APIs
- **Allocation:** `POST /api/admin/outbound/allocate`
  - Body: `{ "so_item_id": 5011, "location_id": 301, "qty": 6, "lot_no": "LOT-XYZ-001", "remarks": "Wave-32" }`
  - Header `X-Idempotency-Key` optional, fallback `ALLOC|hash` based on item/location/qty/lot.
  - Response `201` on first create → `{ movement_id, stock_id, idempotency_key, so_item }`; replay returns `200` with `movement_id` unchanged.
  - Guards: admin_gudang. Errors: 422 (lot missing for tracked item, outstanding SO/stock deficit), 409 (location mismatch).

- **Complete Pick:** `POST /api/admin/outbound/pick/complete`
  - Body: `{ "pick_line_id": 9001, "qty": 6, "picked_at": "2025-10-07T10:15:00+07:00", "remarks": "Batch A" }`
  - Service forms `PickLineData` → `StockService::move('pick', ...)` decreases `qty_on_hand`, retains `qty_allocated` until PoD.
  - Response `201/200` with `{ movement_id, pick_line, so_item }`.

- **Dispatch:** `POST /api/admin/outbound/shipment/dispatch`
  - Body: `{ "shipment_id": 7001, "dispatched_at": "2025-10-07T11:05:00+07:00", "carrier": "Internal Fleet" }`
  - Marks shipment/outbound as dispatched; returns shipment snapshot including nested `outbound_shipment` status.

- **Deliver / PoD:** `POST /api/admin/outbound/shipment/deliver`
  - Accessible to `admin_gudang` & `driver` (with role).
  - Body: `{ "shipment_id": 7001, "signed_by": "Budi", "signed_at": "2025-10-07T12:00:00+07:00", "notes": "No damage" }`
  - Header `X-Idempotency-Key` recommended; fallback `POD|hash(shipment,signed_by,ts,media)`.
  - Response returns `{ pod, shipment, movements[], idempotency_key }`; StockService `'deliver'` moves release residual allocations. Replays reuse same movements.

## Scan API
- **Endpoint:** `POST /api/scan`
- **Headers:**
  - `Authorization: Bearer <token>`
  - `X-Idempotency-Key` optional — fallback uses `hash('SCAN|device|ts|payload')`.
- **Request Body:**

```json
{
  "sku": "SKU-STD-02",
  "qty": 4,
  "direction": "out",
  "location": "RACK-A2",
  "ts": "2025-10-07T12:34:56+07:00",
  "device_id": "HANDHELD-07",
  "lot_no": null
}
```

- **Behaviour:**
  - `direction = "in"` → `StockService::move('inbound_putaway', ...)` (to location).
  - `direction = "out"` → `StockService::move('pick', ...)` (from location) reducing on-hand counts.
  - Lot-tracked items require `lot_no`; inbound auto-creates lot buckets, outbound expects existing stock.
- **Response (`201` on new movement / `200` on replay):** `{ "movement_id": 8801, "type": "pick", "stock_id": 1201, "idempotency_key": "hash...", "applied": true|false }`.
- **Error hints:** 422 for unknown sku/location or missing lot, 409 not used (idempotency resolves via ref).

### Frontend Realtime Snippet (Ably + Laravel Echo)

```js
import Echo from 'laravel-echo';
import Ably from 'ably/promises';

window.Ably = new Ably.Realtime.Promise({ key: import.meta.env.VITE_ABLY_KEY });

window.Echo = new Echo({
  broadcaster: 'ably',
  key: import.meta.env.VITE_ABLY_KEY,
  authEndpoint: '/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${accessToken}`,
    },
  },
});

window.Echo.private('wms.scan.WH1')
  .listen('.StockUpdated', (event) => {
    console.log(`[Scan] ${event.sku} @ ${event.location_code}`, {
      onHand: event.qty_on_hand,
      allocated: event.qty_allocated,
      movedAt: event.moved_at,
    });
  });

window.Echo.private('wms.inbound.grn.WH1')
  .listen('.StockUpdated', (event) => {
    notifyInbound(event);
  });
```

## Admin CRUD & Quick Actions
- Semua rute admin berada di bawah prefix `admin/*` dengan middleware `auth` + `role:admin_gudang`. Cakupan:
  - `admin/locations` (CRUD lokasi dengan guard delete saat stok masih tersedia)
  - `admin/items` (SKU unik, flag lot tracked)
  - `admin/suppliers`
  - `admin/purchase-orders` (header + lines, transaksi, validasi qty)
  - `admin/grn` (quick action posting GRN via GrnService → StockService)
- Dashboard quick actions menaut langsung ke rute di atas untuk percepatan operasional.
- Jalur GRN quick action mengikuti form web yang menghasilkan payload sama dengan API sehingga idempoten tetap terjaga.
