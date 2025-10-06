# Roadmap MVP Monitoring Logistik Gudang

## F0 – Persiapan Repo & QA
- **Scope:** bootstrap Laravel 12 project, pasang lint/test tooling (Pint, PHPStan, Pest), set env lokal.
- **Definition of Done:** composer dependencies terpasang; skrip `composer qa` jalan; pipeline lokal tes sukses; dokumentasi QA dasar tersedia.

## F1 – Diagram & Dokumentasi Proses
- **Scope:** finalisasi ERD, flowchart inbound/outbound/transfer-adjust/cycle-count/driver, validasi stakeholder.
- **Definition of Done:** docs/erd.(png|svg), docs/flows/*.png|svg tersimpan; artefak diverifikasi terhadap requirement domain; asumsi terdokumentasi.

## F2 – Desain Data Final
- **Scope:** susun DBML, hasilkan SQL MySQL 8, review indeks & constraint.
- **Definition of Done:** docs/erd.dbml dan docs/erd.sql up-to-date; constraint unik & foreign key tercantum; disetujui tim backend.

## F3 – Fondasi Backend
- **Scope:** implement auth, role `admin_gudang` & `driver`, master data (warehouse, location, item, supplier, customer), seed awal.
- **Definition of Done:** migrasi & seeder jalan; guard/policy memisah akses; feature test auth & role lulus.

## F4 – Inbound Lifecycle
- **Scope:** modul PO → GRN → Putaway, integrasi dengan StockService.
- **Definition of Done:** endpoint & UI dasar aktif; Post GRN menambah stok, movement tercatat; Pest feature test GRN lulus; blueprint scaffolding sinkron.

## F5 – Outbound Lifecycle
- **Scope:** modul SO → Picking → Packing → Shipping → PoD, integrasi driver app.
- **Definition of Done:** alur pick/ship/pod memodifikasi stok sesuai; driver upload foto & tanda tangan; notifikasi PoD tercatat; test feature picking & shipping lulus.

## F6 – Operasi Stok
- **Scope:** transfer lokasi, adjustment, cycle count dengan audit trail.
- **Definition of Done:** semua operasi stok lewat StockService; histori movement konsisten; laporan selisih cycle count tersedia.

## F7 – Landing Page & Dashboard KPI
- **Scope:** landing modern (glassmorphism, parallax, video background, animasi AOS/Framer) dan dashboard ringkas.
- **Definition of Done:** build Vite lokal menghasilkan `public/build`; Lighthouse skor minimal 85; konten dashboard menampilkan KPI stok/inbound/outbound.

## F8 – Panduan Deploy cPanel
- **Scope:** susun panduan .env, upload artefak, cron, penyesuaian hosting.
- **Definition of Done:** docs/deploy-cpanel.md, .env.example siap; uji coba deploy staging sukses.

## F9 – Laporan & Handover
- **Scope:** rekap keputusan arsitektur, catatan gap, backlog lanjutan.
- **Definition of Done:** laporan akhir diserahkan; repo bersih; dokumentasi handover mencakup kontak, skrip QA, pending issue.
