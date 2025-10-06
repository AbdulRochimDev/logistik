# Arsitektur Lengkap — WMS Logistics (Laravel 12, cPanel) 
**Tujuan:** Menetapkan **arsitektur, konvensi, dan toolchain** agar pengembangan konsisten dan mudah diotomasi oleh AI agent/tim.  
**Stack:** PHP 8.2, Laravel 12, MySQL 8/PlanetScale (3306 TLS), Vite (build lokal), cPanel (shared).  
**Helper:** **Laravel Tinker**, **Pest** (testing), **Laravel Blueprint** (scaffold/consistency).

---

## 1) Struktur Proyek & Layering
```
app/
  Actions/                 # langkah atomik (mis. PostGRNAction, AllocateStockAction)
  Domain/                  # model domain + layanan inti
    Inventory/
      Models/Item.php, ItemLot.php, Stock.php, Location.php
      Services/StockService.php          # update stok & movements (satu pintu)
      DTO/StockMoveData.php              # data terstruktur untuk movement
      Policies/                         # policy khusus domain
      Events/StockMoved.php
      Listeners/UpdateStockCache.php
    Inbound/
      Models/PurchaseOrder.php, GrnHeader.php, GrnLine.php, InboundShipment.php
      Services/GrnService.php           # proses posting GRN
      Jobs/PostGrnJob.php               # opsi async (queue database/sync)
    Outbound/
      Models/SalesOrder.php, SoItem.php, OutboundShipment.php, PickList.php, PickLine.php, Package.php, Shipment.php, Pod.php
      Services/OutboundService.php      # allocate, pick, ship, pod
    Ops/
      Models/Adjustment.php, AdjustmentLine.php, CycleCount.php, CycleCountLine.php, StockMovement.php
  Console/Commands/        # command artisan rutin (housekeeping, recalculation, imports)
  Http/
    Controllers/           # tipis; delegasi ke Services/Actions
    Requests/              # FormRequest validasi
    Resources/             # API Resources (jika REST/SPA)
  Policies/
  Providers/
bootstrap/
config/
database/
  migrations/
  seeders/
  factories/
docs/                        # ERD, flow, deploy guide, SLA, ADR
resources/
  js/                        # React/Vite (build lokal)
  views/                     # Blade
routes/
  web.php, api.php
tests/
  Pest.php, Feature/, Unit/
```

**Prinsip:**  
- **Controller tipis**, logic bisnis di **Services/Actions**.  
- **StockService** adalah **satu-satunya** pintu perubahan `stocks` & `stock_movements` (transaksi DB).  
- **DTO** untuk payload jelas antar layer.  
- **Form Request** untuk validasi.  
- **Event** → side effects (cache/log/audit), **Job** opsional untuk proses berat.  
- **Policy/Middleware Role** untuk akses: `admin_gudang`, `driver`.

---

## 2) Konvensi Penamaan
- Model = singular `PurchaseOrder`, tabel snake plural `purchase_orders`.
- Service = kata kerja + Domain: `GrnService`, `OutboundService`, `StockService`.
- Action = 1 kegiatan atomik: `PostGRNAction`, `AllocateStockAction`.
- Event past tense: `StockMoved`, `GrnPosted`. Listener present: `UpdateStockCache`.
- Route resourceful: `Route::resource('purchase-orders', ...)` + prefix `/admin`.

---

## 3) Transaksi & Batas Konsistensi
- **Semua update stok** dibungkus `DB::transaction()` di `StockService`/Action terkait.  
- **Idempotent**: cek duplikasi movement via `ref_type` + `ref_id`.  
- **Optimistic checks**: validasi `qty_available >= qty_to_allocate/pick`.  
- **Lock ringan**: gunakan `SELECT ... FOR UPDATE` saat critical section (allocate/pick).

---

## 4) Blueprint → Scaffold konsisten
**Laravel Blueprint** membuat **migration, model, controller, form request** dari DSL `draft.yaml`.  
Pasang:
```bash
composer require --dev laravel-shift/blueprint
php artisan vendor:publish --tag=blueprint-config
```
Contoh **`draft.yaml`** (subset inti, bisa dipecah per domain):
```yaml
models:
  Supplier:
    code: string unique
    name: string
    contact: string nullable
    phone: string nullable
    email: string nullable
    address: text nullable
  PurchaseOrder:
    supplier_id: id foreign
    po_no: string unique
    status: string default:draft
    eta: date nullable
    notes: text nullable
    relationships:
      hasMany: PoItem
  PoItem:
    purchase_order_id: id foreign
    item_id: id foreign
    uom: string
    qty_ordered: decimal:16,3
    qty_received: decimal:16,3 default:0
controllers:
  PurchaseOrder:
    resource: api
    store:
      validate: supplier_id, po_no, eta, notes
    update:
      validate: status, eta, notes
seeders: Supplier, Item
```
Generate:
```bash
php artisan blueprint:build        # buat file
php artisan migrate                # jalankan migrasi
```

> Saran: pisahkan `draft.yaml` per modul (`draft-inbound.yaml`, `draft-outbound.yaml`) agar mudah iterasi.

---

## 5) StockService — kontrak inti
```php
namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\{Stock, StockMovement, Item, ItemLot, Location};
use Illuminate\Support\Facades\DB;

class StockService
{
    /** Move/adjust stock in one transaction. */
    public function move(array $data): StockMovement
    {
        // $data: ['type','item_id','lot_id','from_location_id','to_location_id','qty','uom','ref_type','ref_id','actor_user_id','moved_at','remarks']
        return DB::transaction(function() use ($data) {
            // 1) Adjust from_location (if any)
            if (!empty($data['from_location_id'])) {
                $from = Stock::lockForUpdate()->firstOrCreate([
                    'warehouse_id' => $data['warehouse_id'],
                    'location_id'  => $data['from_location_id'],
                    'item_id'      => $data['item_id'],
                    'lot_id'       => $data['lot_id'] ?? null,
                ], ['qty_on_hand'=>0,'qty_allocated'=>0]);
                $from->qty_on_hand -= $data['qty'];
                if ($from->qty_on_hand < 0) throw new \DomainException('Insufficient on hand');
                $from->save();
            }
            // 2) Adjust to_location (if any)
            if (!empty($data['to_location_id'])) {
                $to = Stock::lockForUpdate()->firstOrCreate([
                    'warehouse_id' => $data['warehouse_id'],
                    'location_id'  => $data['to_location_id'],
                    'item_id'      => $data['item_id'],
                    'lot_id'       => $data['lot_id'] ?? null,
                ], ['qty_on_hand'=>0,'qty_allocated'=>0]);
                $to->qty_on_hand += $data['qty'];
                $to->save();
            }
            // 3) Log movement (idempotency key implied by ref_type+ref_id)
            return StockMovement::create($data);
        });
    }
}
```
> Semua modul (GRN, pick, transfer, adjustment) **WAJIB** lewat `StockService`.

---

## 6) Pest Testing — pola & contoh
**Instal:**
```bash
composer require pestphp/pest --dev
php artisan pest:install
```
**Konvensi:**
- `tests/Unit/*Test.php` untuk service/action murni.
- `tests/Feature/*Test.php` untuk endpoint & DB integrasi.
- Gunakan **dataset** untuk variasi kasus, **fakes** untuk Event/Storage.

**Contoh Unit Test — `StockServiceTest.php`:**
```php
use App\Domain\Inventory\Models\{Stock, Location, Item};
use App\Domain\Inventory\Services\StockService;

it('increments on_hand on inbound putaway', function () {
    $svc = app(StockService::class);
    $warehouse_id = 1;
    $loc = Location::factory()->create(['warehouse_id'=>$warehouse_id]);
    $item = Item::factory()->create();
    $svc->move([
        'type' => 'inbound_putaway',
        'warehouse_id' => $warehouse_id,
        'item_id' => $item->id,
        'from_location_id' => null,
        'to_location_id' => $loc->id,
        'qty' => 10,
        'uom' => 'PCS',
        'ref_type' => 'GRN',
        'ref_id' => 1001,
        'actor_user_id' => 1,
        'moved_at' => now(),
        'remarks' => 'test',
    ]);
    $stock = Stock::where(['location_id'=>$loc->id,'item_id'=>$item->id])->first();
    expect($stock->qty_on_hand)->toBe(10.0);
});
```

**Contoh Feature Test — `PostGrnTest.php`:**
```php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('posts GRN and increases stock', function () {
    $admin = User::factory()->create();
    $payload = [
      // grn header/lines minimal
    ];
    $this->actingAs($admin)
        ->post('/admin/inbound/grn', $payload)
        ->assertStatus(302);
    // assert DB stock & movement
});
```

**Script QA (composer.json):**
```json
"scripts": {
  "qa": [
    "php -v",
    "@pint",
    "php -d memory_limit=1G vendor/bin/phpstan analyse --memory-limit=1G",
    "php artisan test --parallel --recreate-databases"
  ]
}
```

---

## 7) Tinker — eksplor & diagnosis cepat
**Buka konsol:**
```bash
php artisan tinker
```
**Snippet siap pakai:**
```php
// Cek stok item per lokasi
App\Domain\Inventory\Models\Stock::where('item_id', 123)->get(['location_id','qty_on_hand','qty_allocated']);

// Simulasi inbound 5 pcs (hati-hati, ini menulis DB)
app(App\Domain\Inventory\Services\StockService::class)->move([
  'type'=>'inbound_putaway', 'warehouse_id'=>1, 'item_id'=>123,
  'from_location_id'=>null, 'to_location_id'=>45, 'qty'=>5, 'uom'=>'PCS',
  'ref_type'=>'TEST', 'ref_id'=>uniqid(), 'actor_user_id'=>1, 'moved_at'=>now(), 'remarks'=>'tinker'
]);

// Cek movement terbaru
App\Domain\Inventory\Models\StockMovement::latest()->first();
```
> Gunakan **.env local** dan DB dummy saat eksperimen; jangan lakukan `move()` di produksi.

---

## 8) Kontrak API/Internal (opsional)
- Buat **FormRequest** untuk semua endpoint perubahan stok (validasi qty>0, lot wajib jika `is_lot_tracked`).  
- Buat **API Resource** untuk serialisasi ringan (stocks, movements, orders).  
- Endpoint versi `/api/v1/*` jika akan dipakai aplikasi mobile driver.

---

## 9) Observers & Audit
- **Model Observers** untuk log `created/updated/deleted` ke `audit_logs` (opsional).  
- Tag setiap movement dengan `actor_user_id` dan `ref_type/ref_id`.

---

## 10) Build Frontend (Vite, build lokal)
- Hindari three.js berat; gunakan dekorasi ringan.  
- Video background: mp4 720p 10–20 detik, `muted playsinline loop`.  
- Jalankan `npm run build` lokal → upload `public/build` ke cPanel.

---

## 11) Deployment Namecheap cPanel (ringkas)
- PHP 8.2, aktifkan `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`.  
- Document root → `/public`.  
- `.env` produksi menggunakan MySQL 3306 TLS.  
- Cron: `*/5 * * * * php /home/<USER>/<APP>/artisan schedule:run`.

---

## 12) Checklist Konsistensi
- Semua perubahan stok melalui **StockService/Actions**.  
- Setiap PR menyertakan **test Pest** (Unit/Feature) minimal 1.  
- Blueprint digunakan untuk scaffold awal & menjaga konsistensi struktur.  
- Tinker digunakan hanya untuk eksplor lokal & debugging terkontrol.  
- Dokumen (ERD/Flow/Deploy) tersimpan di `/docs` dan diperbarui saat ada perubahan skema/alur.
