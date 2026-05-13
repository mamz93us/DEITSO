<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells spatie/laravel-permission which "team" (organization) the current
 * request is operating in. Must run AFTER ResolveOrganizationFromHost +
 * SetActiveOrganization so the binding is set.
 *
 * Without this, $user->hasRole('Org Admin') returns true if the user has that
 * role in ANY org — leaking cross-tenant role data.
 */
class SetSpatieTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('current.organization') && app('current.organization') !== null) {
            $org = app('current.organization');
            $orgId = is_object($org) ? $org->id : $org;

            app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);
        }

        return $next($request);
    }
}
