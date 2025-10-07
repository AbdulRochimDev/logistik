# Logistik WMS (Laravel)

## Local setup

The project targets PHP 8.2+ with OpenSSL 3.0. Composer will fail with an
error similar to `libcrypto.so.1.1: version 'OPENSSL_1_1_1' not found` if the
runtime is linked against OpenSSL 1.1. Use one of the following approaches to
get a compatible toolchain before running `composer install`:

- **mise** (recommended): `mise use --global php@8.2` installs a PHP build that
  already links against OpenSSL 3.0. Restart your shell so that `which php`
  resolves to `~/.local/share/mise/installs/php/8.2/bin/php`.
- **Docker**: run Composer through the supplied container image
  (`docker compose run --rm php composer install`).
- **Homebrew / package manager**: ensure the installed PHP provides `openssl`
  3.0+ (e.g. `brew install php@8.3`).

If you encounter compatibility errors (for example
```
php: /lib/x86_64-linux-gnu/libcrypto.so.1.1: version `OPENSSL_1_1_1' not found
```
follow the remediation steps in [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md).

After PHP is available, install dependencies and prepare the framework cache:

```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=LogisticsDemoSeeder --force
```

Run quality gates locally before submitting changes:

```bash
composer qa
```
