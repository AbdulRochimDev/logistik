<?php

declare(strict_types=1);

if (!function_exists('ensureBootstrapCacheDirectory')) {
    /**
     * Ensure the bootstrap cache directory exists and is writable.
     */
    function ensureBootstrapCacheDirectory(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException("Unable to create the {$path} directory.");
            }
        }

        @chmod($path, 0775);

        if (!is_writable($path)) {
            $testFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.write-test';
            $handle = @fopen($testFile, 'wb');

            if ($handle === false) {
                throw new RuntimeException("The {$path} directory is not writable.");
            }

            fclose($handle);
            @unlink($testFile);
        }
    }
}
