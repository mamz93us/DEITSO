<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization for the request from the Host header.
 *
 * Resolution priority:
 *   1. Verified custom domain in `organization_domains` (host = Host header,
 *      dns_status = verified).
 *   2. Platform subdomain match: {slug}.{APP_PLATFORM_BASE_DOMAIN} → organization
 *      with that slug.
 *   3. Null (master/root host, system-admin context) — leaves the scope unset.
 *
 * Binds `current.organization` as a singleton on the container. Downstream
 * traits (BelongsToOrganization) and middleware read from this.
 *
 * Resilient to early-bootstrap state: if the organizations or organization_domains
 * tables don't yet exist (Sprint 0 — before tenancy migrations land), the
 * middleware silently leaves the binding unset.
 */
class ResolveOrganizationFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $org = $this->resolveByCustomDomain($host) ?? $this->resolveByPlatformSubdomain($host);

        app()->instance('current.organization', $org);

        return $next($request);
    }

    protected function resolveByCustomDomain(string $host): ?object
    {
        if (! Schema::hasTable('organization_domains') || ! Schema::hasTable('organizations')) {
            return null;
        }

        $domain = DB::table('organization_domains')
            ->where('host', $host)
            ->where('dns_status', 'verified')
            ->first();

        if (! $domain) {
            return null;
        }

        return DB::table('organizations')->where('id', $domain->organization_id)->first();
    }

    protected function resolveByPlatformSubdomain(string $host): ?object
    {
        $base = strtolower((string) config('app.platform_base_domain'));
        if ($base === '' || ! str_ends_with($host, '.'.$base)) {
            return null;
        }

        $slug = substr($host, 0, -strlen('.'.$base));
        if ($slug === '' || str_contains($slug, '.')) {
            return null;
        }

        if (! Schema::hasTable('organizations')) {
            return null;
        }

        return DB::table('organizations')->where('slug', $slug)->first();
    }
}
