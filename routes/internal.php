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
| health probes, etc.). Bind to an internal network only in production.
|
*/

Route::get('/internal/domains/allow', function (Request $request) {
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
