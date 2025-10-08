<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/cache_directory.php';

try {
    ensureBootstrapCacheDirectory(__DIR__ . '/../bootstrap/cache');
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
