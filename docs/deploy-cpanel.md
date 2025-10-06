# Panduan Deploy Laravel 12 ke Namecheap cPanel

## Prasyarat Server
- PHP 8.2 dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `bcmath`, `ctype`, `json`.
- Composer 2 tersedia via Terminal cPanel atau upload vendor dari lokal.
- MySQL 8 (port 3306) dengan TLS aktif; catat host, username, password, nama database.

## Struktur Direktori
1. Buat folder aplikasi, contoh `/home/<user>/logistics-monitoring`.
2. Upload seluruh isi repo ke folder tersebut (kecuali `vendor/` jika ingin install di server).
3. Atur Document Root domain/subdomain ke `<app>/public` melalui `Domains > Document Root`.
4. Pastikan folder `storage/` dan `bootstrap/cache/` writable (`755` atau `775`).

## Build Frontend (Vite)
1. Jalankan build lokal: `npm install && npm run build`.
2. Upload hasil `public/build/` ke server (replace jika ada versi lama).

## Konfigurasi .env
1. Salin `.env.example` menjadi `.env` di server.
2. Atur nilai penting:
   - `APP_NAME="Monitoring Gudang"`
   - `APP_ENV=production`
   - `APP_URL=https://domainanda.com`
   - `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `DB_SSL_CA` jika Namecheap menyediakan sertifikat CA.
   - `SESSION_DRIVER=database`, `CACHE_DRIVER=redis` (opsional) — sesuaikan layanan.
3. Generate key: `php artisan key:generate --force`.

## Instalasi Dependensi
- Via SSH Terminal cPanel:
  ```bash
  cd /home/<user>/logistics-monitoring
  composer install --optimize-autoloader --no-dev
  php artisan migrate --force
  php artisan db:seed --force
  php artisan storage:link
  ```
- Jika tidak ada Composer di server, install lokal dan upload folder `vendor/`.

## Cron Scheduler
Tambahkan cron job (cPanel > Cron Jobs):
```
*/5 * * * * php /home/<user>/logistics-monitoring/artisan schedule:run >> /home/<user>/logs/schedule.log 2>&1
```
Pastikan folder `logs/` ada dan writable.

## Optimasi Produksi
```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Checklist Pasca Deploy
- Cek izin `storage/logs/laravel.log`.
- Buka landing page memastikan aset `public/build` termuat tanpa error 404.
- Jalankan `php artisan queue:work --tries=3` via Supervisor atau cron jika ada job asinkron.
- Validasi integrasi driver PoD (upload foto + tanda tangan) di environment produksi.
