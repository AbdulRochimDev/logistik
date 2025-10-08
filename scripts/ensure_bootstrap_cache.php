<?php

declare(strict_types=1);

$path = __DIR__ . '/../bootstrap/cache';

if (!is_dir($path)) {
    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        fwrite(STDERR, "Unable to create the {$path} directory." . PHP_EOL);
        exit(1);
    }
}

@chmod($path, 0775);

if (!is_writable($path)) {
    $testFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.write-test';

    $handle = @fopen($testFile, 'wb');

    if ($handle === false) {
        fwrite(STDERR, "The {$path} directory is not writable." . PHP_EOL);
        exit(1);
    }

    fclose($handle);
    @unlink($testFile);
}
