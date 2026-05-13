<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replay-protects state-changing API requests using the Idempotency-Key header.
 *
 * Caches the full response payload for 24h, keyed by:
 *   org_id (or "system") + user_id (or "guest") + Idempotency-Key
 *
 * On a repeat request with the same key, returns the cached response with an
 * `X-Idempotent-Replayed: 1` header. GET/HEAD/OPTIONS requests are passed
 * through without caching. Requests without the header are also passed through.
 */
class IdempotencyKey
{
    public const TTL_SECONDS = 86400; // 24 hours

    public const HEADER = 'Idempotency-Key';

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $key = $request->header(self::HEADER);
        if (! is_string($key) || $key === '') {
            return $next($request);
        }

        $cacheKey = $this->cacheKey($request, $key);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response($cached['body'], $cached['status'], $cached['headers'] ?? [])
                ->header('X-Idempotent-Replayed', '1');
        }

        $response = $next($request);

        Cache::put($cacheKey, [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(),
            'headers' => $this->filterHeaders($response->headers->all()),
        ], self::TTL_SECONDS);

        return $response;
    }

    protected function cacheKey(Request $request, string $key): string
    {
        $org = app()->bound('current.organization') ? app('current.organization') : null;
        $orgId = $org ? (is_object($org) ? $org->id : $org) : 'system';
        $userId = $request->user()?->getAuthIdentifier() ?? 'guest';

        return sprintf('idempotency:%s:%s:%s', $orgId, $userId, hash('sha256', $key));
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, array<int, string>>
     */
    protected function filterHeaders(array $headers): array
    {
        $strip = ['date', 'set-cookie', 'cache-control'];

        return array_diff_key(
            $headers,
            array_flip($strip),
        );
    }
}
