# Deploying to Vercel

This guide walks through the minimum configuration required to run the
Logistik WMS Laravel application on [Vercel](https://vercel.com/). The focus is
on preparing environment variables, understanding build/runtime behaviour, and
triggering the first deploy with a managed MySQL and optional Redis backend.

## 1. Prerequisites

1. **Vercel account & project** – either import this repository or connect it to
   a GitHub/GitLab/Bitbucket integration. The guide assumes the project is
   linked to Vercel so each push triggers a deployment.
2. **Managed database** – Vercel does not ship with a database. Provision a
   MySQL-compatible service (e.g. Vercel MySQL, PlanetScale, Neon with
   MySQL), note the hostname, port, database, username, password, and TLS CA
   certificate path/URL.
3. **Optional Redis** – for higher throughput caching and session locking, use
   a managed Redis such as Upstash. If unavailable, the app falls back to the
   database cache driver defined later in this document.
4. **AWS-compatible object storage** – configure Amazon S3, MinIO, or another
   provider for file uploads referenced by the outbound/driver modules.

## 2. Environment variables

Vercel loads environment variables from its dashboard; the repository contains
`.env.vercel.example` with the recommended variables and comments.

1. Visit **Project Settings → Environment Variables** in Vercel.
2. Copy each key from `.env.vercel.example` and supply the value that matches
   your infrastructure.
3. Generate a production application key locally and paste it into `APP_KEY`:

   ```bash
   php artisan key:generate --show
   ```

4. Set `APP_URL` to the public HTTPS domain that Vercel serves (e.g.
   `https://logistik.vercel.app`). Reuse that domain for
   `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN` (prefix with a dot to cover
   subdomains when needed, e.g. `.vercel.app`).
5. If you rely on cookies across HTTPS, keep `SESSION_SECURE_COOKIE=true`.
6. For the queue/cache tables, reuse the MySQL credentials used for the main
   application database.

> **Tip:** Vercel supports [Environment Variable Imports](https://vercel.com/docs/projects/environment-variables#environment-variable-imports).
> Uploading a copy of `.env.vercel.example` is the quickest way to create all
> the keys at once.

## 3. Build and runtime settings

1. In **Project Settings → Build & Development Settings**, set:
   - **Framework Preset:** `Other` (Laravel does not have an official preset).
   - **Build Command:** `composer install --no-dev --prefer-dist --optimize-autoloader && npm ci && npm run build`.
   - **Output Directory:** `public` (the Laravel public directory is served).
   - **Install Command:** leave blank so Vercel runs the build command above.
2. Add a root `vercel.json` (optional) if you need rewrites/redirects; Laravel
   already routes traffic through `public/index.php` so rewrites are usually not
   required.
3. Ensure `APP_TRUSTED_PROXIES="*"` is set (already in
   `.env.vercel.example`) so Laravel honours the proxy headers Vercel injects.

## 4. Database migrations and seeding

Deployments on Vercel are immutable, so run migrations against the managed
database from your local machine or a CI pipeline:

```bash
php artisan migrate --force
php artisan db:seed --force
```

The commands require the same environment variables that production uses. You
can temporarily export them in your shell or rely on `vercel env pull` after
importing the environment variables to Vercel.

## 5. Verify the deployment

1. Trigger a new deployment (push to the main branch or click **Deploy**).
2. Wait for the build to complete; the preview/production URLs should respond
   with the Laravel dashboard login page.
3. Sign in using the seeded admin credentials (e.g. the values supplied in the
   environment variables above) and confirm stock dashboards load correctly.
4. Review Vercel **Logs** to ensure no connection or TLS errors occur when
   hitting the managed services.

## 6. Troubleshooting

- **500 errors immediately after deploy** – verify `APP_KEY`, `APP_URL`, and
  database credentials. Laravel will fail to boot when the key is missing.
- **Session/cookie issues** – confirm `SESSION_DOMAIN` includes a leading dot
  when using custom domains and keep `SESSION_SECURE_COOKIE=true` on HTTPS.
- **Migrations failing** – ensure your MySQL instance allows TLS (set
  `DB_SSL=false` temporarily only when required) and that the configured user
  has privileges to create tables.
- **Asset 404s** – double check `npm run build` completes successfully and that
  `ASSET_URL` points to the same domain as `APP_URL` or an S3 CDN domain when
  using object storage.

Following the steps above ensures the Laravel application boots correctly on
Vercel and that the supporting infrastructure (database, cache, storage) is
connected for the inbound/outbound logistics workflows.

