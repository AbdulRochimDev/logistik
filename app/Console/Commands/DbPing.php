<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DbPing extends Command
{
    protected $signature = 'db:ping {--connection= : Database connection name} {--verbose|-v : Show connection details}';

    protected $description = 'Execute a healthcheck query against the configured database connection.';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$connection}");

        if ($config === null) {
            $this->error(sprintf('Database connection [%s] is not defined.', $connection));

            return self::FAILURE;
        }

        try {
            $this->ensureCertificateExists($connection, $config);
            $result = DB::connection($connection)->select('select 1 as ok');
        } catch (Throwable $exception) {
            $this->error(sprintf('Database ping failed for connection [%s]: %s', $connection, $exception->getMessage()));

            return self::FAILURE;
        }

        if (((int) ($result[0]->ok ?? 0)) !== 1) {
            $this->error(sprintf('Database ping returned unexpected value for connection [%s].', $connection));

            return self::FAILURE;
        }

        if ($this->output->isVerbose()) {
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? '3306';
            $database = $config['database'] ?? '';
            $username = $config['username'] ?? '';
            $options = $config['options'] ?? [];
            $caPath = null;

            if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $caPathKey = constant('PDO::MYSQL_ATTR_SSL_CA');
                $caPath = $options[$caPathKey] ?? null;
            }

            $this->line(sprintf('Connection: %s@%s:%s / %s', $username, $host, $port, $database));
            $this->line('SSL: '.($caPath ? sprintf('ENABLED (CA: %s)', $caPath) : 'disabled'));
        }

        $this->info(sprintf('Database connection [%s] responded successfully.', $connection));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function ensureCertificateExists(string $connection, array $config): void
    {
        if (! defined('PDO::MYSQL_ATTR_SSL_CA')) {
            return;
        }

        $sslCaKey = constant('PDO::MYSQL_ATTR_SSL_CA');
        $options = $config['options'] ?? [];
        $caPath = $options[$sslCaKey] ?? null;

        if ($caPath === null) {
            return;
        }

        if (! is_string($caPath) || $caPath === '' || ! file_exists($caPath)) {
            throw new RuntimeException(sprintf(
                'DB_SSL is enabled for connection [%s] but CA file was not found at [%s]. Update DB_CA_PATH or disable DB_SSL.',
                $connection,
                $caPath ?: '(empty)'
            ));
        }
    }
}
