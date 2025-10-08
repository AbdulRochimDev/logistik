# Git Push Guide

Repositori ini tidak menyiapkan remote secara otomatis. Gunakan panduan berikut untuk mendorong
(`push`) cabang `work` ke repo pribadi Anda. Bagian pertama memuat ringkasan cepat dalam bahasa
Indonesia, diikuti referensi rinci yang tetap relevan untuk alur kerja Git standar.

> **Tip kilat:** jika Anda hanya butuh komando ringkas, lompat ke [Quick Start](#quick-start).

Sebelum mendorong perubahan, jalankan checklist pra-push berikut agar QA tetap hijau:

## Pre-Push Checklist

1. `composer install --no-interaction --no-progress`
2. `composer dump-autoload --no-scripts`
3. `composer qa` (menjalankan Pint → PHPStan → Pest)
4. `php artisan migrate --force` (pastikan migrasi baru aman)
5. `npm install && npm run build` bila ada perubahan front-end/Vite

## Quick Start

```bash
# 1. Tambahkan remote (opsional bila belum ada)
git remote add origin git@github.com:your-account/logistik.git

# 2. Verifikasi status kerja & jalankan checklist QA (lihat di atas)
git status -sb

# 3. Dorong cabang `work`
git push -u origin work
```

Jika remote sudah ada, Anda dapat langsung menjalankan langkah 2 dan 3. Berikut bagian rinci bila
Anda membutuhkan konteks tambahan.

## Mengonfigurasi Remote (Rinci)

1. Add your remote once (replace `origin` with your preferred remote name and adjust the URL):

   ```bash
   git remote add origin git@github.com:your-account/logistik.git
   ```

   You can verify the remote with `git remote -v`.

2. Ensure your local branch is up to date and staged commits are ready. The status command should
   show a clean working tree:

   ```bash
   git status -sb
   ```

3. (Opsional) Uji koneksi SSH bila menggunakan `git@github.com`:

   ```bash
   ssh -T git@github.com
   ```

   GitHub akan menampilkan pesan sambutan jika kunci SSH Anda sudah terhubung dengan akun yang
   benar.

4. Push the current `work` branch to the remote:

   ```bash
   git push -u origin work
   ```

   The `-u` flag sets `origin/work` as the default upstream so future `git push` and `git pull`
   commands can omit the remote and branch name.

5. If you update the branch later, simply run:

   ```bash
   git push
   ```

## Troubleshooting

- **Remote already exists** – If you previously added a remote with the same name, either remove it
  with `git remote remove origin` or use a different name (e.g., `git remote add upstream ...`).
- **Authentication issues** – Ensure your SSH key or HTTPS credentials are configured for the host.
  For GitHub, refer to <https://docs.github.com/en/authentication>.
- **Rejected push** – Pull and rebase/merge changes from the remote branch before pushing again:

  ```bash
  git pull --rebase
  git push
  ```

- **HTTPS + Personal Access Token (PAT)** – Jika lebih nyaman memakai HTTPS, ganti langkah `git
  remote add` dengan URL berbasis HTTPS dan gunakan PAT sebagai password ketika diminta:

  ```bash
  git remote add origin https://github.com/your-account/logistik.git
  git push -u origin work
  ```

- **Force push aman** – Bila Anda perlu memperbarui riwayat commit setelah rebase, gunakan
  `git push --force-with-lease` agar tidak menimpa pekerjaan rekan tanpa sengaja.
