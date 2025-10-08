# Changelog
# 2025-10-09
- Milestone 3: pengerasan Driver API (FormRequest guard qty/status, policy assignment, idempotency header) plus rate limiter 30/menit.
- Memindahkan penyimpanan PoD ke disk `wms.storage.pod_disk` (default S3) dengan metadata device/UA serta generator URL sementara untuk admin UI.
- Menambahkan badge replay & tombol presigned link pada Shipment Show, menampilkan PoD signer dan status idempotent.
- Suite Pest baru: `DriverPickApiInvalidQtyTest`, `DriverUnauthorizedAccessTest`, `DriverDispatchRulesTest`, `DriverPodIdempotencyTest`, `DriverApiRateLimitTest`, `PodUploadStorageTest`, `ShipmentAdminPodViewTest`.
- Dokumentasi diperbarui (INTEGRATION.md, WORKFLOW.md, GIT_PUSH.md) dengan kontrak Driver API, storage PoD, checklist Vercel, dan pra-push QA.

# 2025-10-08
- Milestone 2: menambahkan event realtime outbound (`PickCompleted`, `ShipmentDispatched`, `ShipmentDelivered`) dengan emisi pasca-commit dan channel privat `wms.outbound.shipment.{id}`.
- Membuat partial Blade realtime yang memuat Ably + Laravel Echo, menggabungkan aktivitas terbaru ke dashboard dan memperbarui progress shipment tanpa reload.
- Memperluas dashboard admin dengan KPI open shipments/picked today/delivered today, agregasi warehouse harian, serta feed gabungan stock movement + event outbound.
- Menambahkan progress pick live di halaman shipment show, lengkap dengan data attribute untuk pembaruan via event.
- Menyertakan konfigurasi broadcasting (`config/broadcasting.php`), guard channel, dan dokumentasi integrasi (INTEGRATION.md & WORKFLOW.md) yang mencakup diagram alur baru.
- Menambahkan suite Pest `RealtimeBroadcastTest`, `PostCommitEmissionTest`, `DashboardOutboundStatsTest`, dan `ShipmentShowRealtimeWiringTest` untuk memverifikasi alur realtime & KPI.
## 2025-10-07
- Added consolidated logistics core migration covering roles, warehouse/location/item master data, PO/GRN tables, stocks, and stock_movements.
- Introduced stock movement idempotency indexes and generated column for qty_available.
- Added domain models with relationships plus factories and LogisticsDemoSeeder for baseline dataset.
- Implemented inbound GRN DTOs, request validation, service orchestration, and API controller using StockService.
- Added external idempotency key column on `grn_headers` plus deterministic fallback for requests without `X-Idempotency-Key`.
- Hardened GRN validation (lot required, warehouse-location match) and response metadata (processed/skipped lines) for replay safety.
- Expanded Pest coverage for StockService idempotency and end-to-end GRN posting scenarios.
- Documented inbound workflow in docs/WORKFLOW.md and integration contract in docs/INTEGRATION.md.
- Delivered outbound orchestration (allocation, pick completion, dispatch, delivery) with dedicated DTOs/controllers and role-protected routes.
- Extended OutboundService to maintain stock/allocated balances and idempotent PoD handling via `deliver` movements.
- Added `/api/scan` endpoint with deterministic idempotency, mapping handheld scans to StockService moves, plus feature coverage validating lot requirements and replays.
- Refreshed LogisticsDemoSeeder with outbound dataset (SO, pick lines, shipment) and updated docs/INTEGRATION.md + docs/WORKFLOW.md to cover outbound and scan flows.
- Hardened TiDB TLS configuration via `config/database.php` (auto absolute CA paths, explicit errors when CA hilang) dan menambahkan helper `db:ping` dengan output verbose.
- Menambahkan `AdminUserSeeder` (password dari `ADMIN_PASSWORD`) + suite Pest baru untuk koneksi DB, autentikasi login, dan broadcast Ably.
- Meng-update docs/INTEGRATION.md & docs/WORKFLOW.md dengan langkah verifikasi (`config:clear`, `db:ping -v`, seeding AdminUser`) serta catatan penempatan sertifikat CA.
- Menambahkan CRUD admin (lokasi, supplier, item, purchase order) + quick action GRN di dashboard; semua rute berada di prefix `admin/*` dengan policy `role:admin_gudang` dan validasi bisnis (delete guard, transaksi, idempoten).
- Menyediakan Blade view baru & partial reusable (tabel performa gudang) beserta skrip ringan untuk form PO/GRN.
- Menambah suite Pest: `LocationCrudTest`, `ItemCrudTest`, `PurchaseOrderCrudTest`, `PostGrnFeatureTest`, `DashboardStatsTest` untuk memastikan alur admin end-to-end.
- Menstabilkan pipeline QA: menambahkan docblock relasi pada model inti, menjaga guard/exception sehingga PHPStan mengenali struktur domain, dan menguatkan OutboundService agar tidak mengandalkan nullsafe yang rapuh.
- Menyediakan jembatan konfigurasi `config/wms.php` sehingga seeder & test tidak lagi bergantung langsung pada `env()`, sekaligus memperbarui helper DatabaseSsl.
- Membuat fallback Sanctum mandiri (`App\Support\Auth\Concerns\HasApiTokensFallback`) plus unit test, memastikan aplikasi tetap berjalan tanpa paket `laravel/sanctum` sambil siap mengadopsi trait asli bila dipasang.
- Menambah suite Pest unit `SanctumFallbackTest` dan `ConfigBridgeTest`, dan membersihkan import/gaya melalui Pint agar `composer qa` kembali hijau.
