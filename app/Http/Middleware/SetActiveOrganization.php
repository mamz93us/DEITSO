<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fallback resolver. If ResolveOrganizationFromHost left `current.organization`
 * null (e.g. on localhost or the master domain), resolve in this order:
 *
 *   1. Session — `active_organization_id` (set by the org switcher).
 *   2. Sanctum token claim — `active_organization_id:{ulid}` ability (mobile/agent API).
 *   3. The authenticated user's default org membership (organization_user.is_default).
 *   4. The authenticated user's first org membership.
 *
 * Once an org is resolved, it is persisted to the session so subsequent
 * requests skip the DB lookup. The session key is the org switcher's hook —
 * a future widget will write to it directly.
 *
 * System Admins (users.is_system_admin = true) with no membership remain
 * unbound — `current.organization` stays null, which OrganizationScope
 * interprets as "system context, span all orgs". Org-scoped users with no
 * membership at all should never reach this middleware (Filament's
 * canAccessPanel blocks them at the auth layer).
 */
class SetActiveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('current.organization') && app('current.organization') !== null) {
            return $next($request);
        }

        if (! Schema::hasTable('organizations')) {
            return $next($request);
        }

        $orgId = $this->resolveFromSession($request)
            ?? $this->resolveFromToken($request)
            ?? $this->resolveFromUserMembership($request);

        if (! $orgId) {
            return $next($request);
        }

        $org = DB::table('organizations')->where('id', $orgId)->first();
        if ($org) {
            app()->instance('current.organization', $org);

            // Persist for the next request — saves an extra membership lookup.
            if ($request->hasSession() && $request->session()->get('active_organization_id') !== $orgId) {
                $request->session()->put('active_organization_id', $orgId);
            }
        }

        return $next($request);
    }

    protected function resolveFromSession(Request $request): ?string
    {
        $id = $request->hasSession()
            ? $request->session()->get('active_organization_id')
            : null;

        return is_string($id) ? $id : null;
    }

    protected function resolveFromToken(Request $request): ?string
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        $token = $user->currentAccessToken();
        if (! $token || ! property_exists($token, 'abilities')) {
            return null;
        }

        foreach (($token->abilities ?? []) as $ability) {
            if (str_starts_with($ability, 'active_organization_id:')) {
                return substr($ability, strlen('active_organization_id:'));
            }
        }

        return null;
    }

    protected function resolveFromUserMembership(Request $request): ?string
    {
        $user = $request->user();
        if (! $user || ! Schema::hasTable('organization_user')) {
            return null;
        }

        // System admins without explicit membership stay unbound — the
        // OrganizationScope treats unbound as "system context".
        if (($user->is_system_admin ?? false) === true) {
            // Still try to bind their default membership if they have one
            // (system admins occasionally also belong to a particular org).
            $orgId = DB::table('organization_user')
                ->where('user_id', $user->getAuthIdentifier())
                ->where('is_default', true)
                ->value('organization_id');

            return is_string($orgId) ? $orgId : null;
        }

        $orgId = DB::table('organization_user')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('is_default')
            ->orderBy('joined_at')
            ->value('organization_id');

        return is_string($orgId) ? $orgId : null;
    }
}
