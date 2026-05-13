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
 * null (master domain login flow), this middleware tries to pick one from:
 *
 *   1. Session: `active_organization_id` — what the user picked in the org switcher.
 *   2. Sanctum token claim: `active_organization_id` ability — for mobile/agent API.
 *
 * If neither resolves and the user is system-admin, leaves null (System Admin can
 * span all orgs). Org-scoped users without an active org are redirected to the
 * switcher in later sprints; here we no-op.
 */
class SetActiveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('current.organization') && app('current.organization') !== null) {
            return $next($request);
        }

        $orgId = $request->session()->get('active_organization_id');

        if (! $orgId && $request->user() && method_exists($request->user(), 'tokenCan')) {
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'can')) {
                foreach (($token->abilities ?? []) as $ability) {
                    if (str_starts_with($ability, 'active_organization_id:')) {
                        $orgId = substr($ability, strlen('active_organization_id:'));
                        break;
                    }
                }
            }
        }

        if ($orgId && Schema::hasTable('organizations')) {
            $org = DB::table('organizations')->where('id', $orgId)->first();
            if ($org) {
                app()->instance('current.organization', $org);
            }
        }

        return $next($request);
    }
}
