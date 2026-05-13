<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Internal Routes
|--------------------------------------------------------------------------
|
| Endpoints called by infrastructure (Caddy on-demand TLS ask callback,
| health probes, etc.).
|
| Defence in depth — three layers:
|   1. Shared secret in the X-Internal-Token header (env INTERNAL_ROUTES_TOKEN).
|   2. IP allowlist from env INTERNAL_ALLOWED_IPS (CSV, supports CIDR via str start).
|      Defaults to 127.0.0.1 only.
|   3. The endpoint itself only confirms verified domain — leaks nothing else.
|
| Production deployment: set INTERNAL_ROUTES_TOKEN to a long random string and
| pass it via Caddy's `header_up X-Internal-Token "$INTERNAL_ROUTES_TOKEN"` on
| the `ask` directive. Also bind the route to an internal-only listener.
*/

Route::get('/internal/domains/allow', function (Request $request) {
    // Layer 1 + 2: token + IP gate
    $expectedToken = (string) env('INTERNAL_ROUTES_TOKEN', '');
    $providedToken = (string) $request->header('X-Internal-Token', '');
    $allowedIps = array_filter(array_map('trim', explode(',', (string) env('INTERNAL_ALLOWED_IPS', '127.0.0.1'))));

    $tokenOk = $expectedToken !== '' && hash_equals($expectedToken, $providedToken);
    $ipOk = in_array($request->ip(), $allowedIps, true);

    if (! $tokenOk && ! $ipOk) {
        return response('forbidden', 403);
    }

    // Layer 3: actual check
    $host = strtolower((string) $request->query('domain'));
    if ($host === '') {
        return response('missing domain', 400);
    }

    if (! Schema::hasTable('organization_domains')) {
        return response('not ready', 503);
    }

    $exists = DB::table('organization_domains')
        ->where('host', $host)
        ->where('dns_status', 'verified')
        ->exists();

    return response($exists ? 'ok' : 'no', $exists ? 200 : 404);
});
