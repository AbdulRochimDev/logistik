# Troubleshooting

## `libcrypto.so.1.1: version 'OPENSSL_1_1_1' not found`

If you see this error while running `composer` or `php`, the PHP binary on your
machine was compiled against OpenSSL 1.1 while your operating system only ships
OpenSSL 3.0. You can resolve it with one of the following approaches:

1. **Install a matching PHP build**: Using a version manager such as
   [mise](https://github.com/jdx/mise) or [asdf](https://asdf-vm.com/) run
   `mise use --global php@8.2` (or the asdf equivalent). These distributions
   link against OpenSSL 3.0 so they work out of the box on Debian 12/Ubuntu 22.04
   and newer.
2. **Use the Docker toolchain**: The project ships a `docker-compose.yml` with a
   PHP container that already has the correct OpenSSL runtime. Run
   `docker compose run --rm php composer install` to execute Composer using the
   containerized environment. You can reuse the same container for all other
   artisan and test commands.
3. **Install the OpenSSL 1.1 compatibility package**: On Debian/Ubuntu you can
   install the legacy OpenSSL runtime by adding the Debian 11 repository and
   running `sudo apt-get install -y libssl1.1`. This satisfies the dependency
   for older PHP builds, though keeping multiple OpenSSL versions installed is
   not recommended long term.

After switching to a compatible PHP runtime, re-run `composer install` and the
error should disappear.
