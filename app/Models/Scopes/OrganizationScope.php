<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Filters tenant-owned queries by the active organization.
 *
 * Contract:
 *   - current.organization bound to an org row → filter by that org_id.
 *   - current.organization bound to null AND the authenticated user is a system
 *     admin (or there is no authenticated user — CLI/console) → no filter
 *     applied (span-all-orgs).
 *   - current.organization bound to null AND the authenticated user is NOT a
 *     system admin → defensive filter `1=0` (return zero rows).
 *
 * This last case is the security backstop: if an org-scoped user reaches a
 * tenant query without an org context (e.g. middleware misconfiguration),
 * we must NOT leak other orgs' data — we return nothing instead.
 *
 * Callers that legitimately need cross-tenant queries (background jobs, the
 * Caddy allow-list, license expiry sweeper, system-admin tooling) explicitly
 * call ->withoutGlobalScopes() or ->withoutGlobalScopes([OrganizationScope::class]).
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $bound = app()->bound('current.organization');
        $org = $bound ? app('current.organization') : null;

        if ($org !== null) {
            $orgId = is_object($org) ? $org->id : $org;
            $builder->where($model->getTable().'.organization_id', $orgId);

            return;
        }

        // Unbound or null. Allow the query through (no filter) ONLY for:
        //   - unauthenticated / CLI context  (Auth::check() === false)
        //   - authenticated system admin
        $user = Auth::user();
        $isSystemContext = ! $user || ($user->is_system_admin ?? false) === true;

        if ($isSystemContext) {
            return;
        }

        // Defensive: an authenticated org-user with no resolved org sees zero rows.
        $builder->whereRaw('1 = 0');
    }
}
