<?php

namespace App\Support\Idempotency;

use App\Support\Idempotency\Exceptions\IdempotencyException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IdempotencyManager
{
    public function resolve(Request $request, string $context, array $fingerprintParts = []): string
    {
        $headerKey = trim((string) $request->headers->get('X-Idempotency-Key', ''));
        $bodyKey = trim((string) $request->input('idempotency_key', ''));

        $key = $headerKey !== ''
            ? $headerKey
            : ($bodyKey !== ''
                ? $bodyKey
                : $this->fingerprint($context, $fingerprintParts ?: $this->defaultFingerprint($request)));

        return $this->persist($request, $context, $key, $fingerprintParts);
    }

    protected function fingerprint(string $context, array $parts): string
    {
        $normalizedContext = Str::of($context)
            ->replace(['/', ' '], '.')
            ->upper();

        $payload = array_map(fn ($part) => $this->stringify($part), $parts);

        return $normalizedContext.'|'.hash('sha256', implode('|', $payload));
    }

    protected function defaultFingerprint(Request $request): array
    {
        return [
            $request->method(),
            $request->fullUrl(),
            $request->all(),
        ];
    }

    protected function persist(Request $request, string $context, string $key, array $parts): string
    {
        $requestHash = hash('sha256', json_encode([
            'headers' => $this->filteredHeaders($request),
            'payload' => $request->all(),
            'parts' => $parts,
        ], JSON_UNESCAPED_SLASHES));

        /** @var IdempotencyKey|null $existing */
        $existing = IdempotencyKey::query()
            ->where('context', $context)
            ->where('key', $key)
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $requestHash) {
                throw new IdempotencyException('Idempotency key has been used with different payload.');
            }

            $existing->forceFill([
                'last_used_at' => Carbon::now(),
            ])->save();

            return $existing->key;
        }

        IdempotencyKey::query()->create([
            'context' => $context,
            'key' => $key,
            'request_hash' => $requestHash,
            'last_used_at' => Carbon::now(),
        ]);

        return $key;
    }

    protected function filteredHeaders(Request $request): array
    {
        $headers = collect($request->headers->all())
            ->map(fn ($values) => Arr::wrap($values)[0] ?? null)
            ->reject(fn ($_, $key) => in_array(strtolower($key), ['authorization', 'cookie'], true))
            ->sortKeys();

        return $headers->all();
    }

    protected function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
