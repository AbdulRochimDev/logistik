<?php

namespace App\Support\Storage;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Generate temporary URLs that work with remote (e.g. S3) and local storage.
 */
class TemporaryUrlGenerator
{
    public function generate(string $disk, string $path, int $ttlSeconds = 900): ?string
    {
        $filesystem = Storage::disk($disk);

        if (! $this->fileExists($filesystem, $path)) {
            return null;
        }

        if (method_exists($filesystem, 'temporaryUrl')) {
            return $filesystem->temporaryUrl($path, CarbonImmutable::now()->addSeconds($ttlSeconds));
        }

        if (method_exists($filesystem, 'url')) {
            return $filesystem->url($path);
        }

        return Storage::url($path);
    }

    private function fileExists(Filesystem $filesystem, string $path): bool
    {
        try {
            return $filesystem->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }
}
