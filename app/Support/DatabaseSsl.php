<?php

namespace App\Support;

use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class DatabaseSsl
{
    /**
     * Build SSL options for MySQL/TiDB connections based on environment flags.
     *
     * @return array<int|string, mixed>
     */
    public static function mysqlOptions(): array
    {
        if (! extension_loaded('pdo_mysql')) {
            return [];
        }

        $useSsl = (bool) config('wms.database.ssl.enabled', false);

        if (! $useSsl) {
            return [];
        }

        $caPath = config('wms.database.ssl.ca_path');

        if ($caPath !== null && $caPath !== '' && ! Str::startsWith($caPath, ['/', '\\'])) {
            $caPath = base_path($caPath);
        }

        if (! is_string($caPath) || $caPath === '' || ! file_exists($caPath)) {
            $original = config('wms.database.ssl.ca_path') ?: '(empty)';

            throw new RuntimeException(
                sprintf(
                    'DB_SSL is enabled but CA file was not found at [%s]. Verify DB_CA_PATH or disable DB_SSL.',
                    $original
                )
            );
        }

        $options = [
            PDO::MYSQL_ATTR_SSL_CA => $caPath,
        ];

        $verify = config('wms.database.ssl.verify_server_cert');

        if ($verify !== null) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = filter_var(
                $verify,
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE
            ) ?? false;
        }

        return $options;
    }
}
