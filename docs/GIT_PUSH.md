# Git Push Guide

This repository does not configure a remote by default. To publish the `work` branch to your own
remote, follow these steps. Sebelum mendorong perubahan, jalankan checklist pra-push berikut agar QA tetap hijau:

## Pre-Push Checklist

1. `composer install --no-interaction --no-progress`
2. `composer dump-autoload --no-scripts`
3. `composer qa` (menjalankan Pint → PHPStan → Pest)
4. `php artisan migrate --force` (pastikan migrasi baru aman)
5. `npm install && npm run build` bila ada perubahan front-end/Vite

## Mengonfigurasi Remote

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

3. Push the current `work` branch to the remote:

   ```bash
   git push -u origin work
   ```

   The `-u` flag sets `origin/work` as the default upstream so future `git push` and `git pull`
   commands can omit the remote and branch name.

4. If you update the branch later, simply run:

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

