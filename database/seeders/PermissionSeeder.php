<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds every Web v1 permission key from PROJECT.md Section 5.
 *
 * Permissions are global (team_id=null) — they are *definitions*. Roles are
 * what get scoped per organization (handled by RoleSeeder).
 */
class PermissionSeeder extends Seeder
{
    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [
            // Org management
            'org.branch.create', 'org.branch.update', 'org.branch.delete',
            'org.employee.invite',
            'org.branding.edit',
            'org.domain.manage',

            // Employee
            'employee.profile.create', 'employee.profile.update', 'employee.profile.terminate',

            // ITAM — suppliers
            'itam.supplier.create', 'itam.supplier.update', 'itam.supplier.delete',

            // ITAM — asset models
            'itam.asset_model.create', 'itam.asset_model.update', 'itam.asset_model.delete',

            // ITAM — assets
            'itam.asset.create', 'itam.asset.update', 'itam.asset.transfer',
            'itam.asset.scrap', 'itam.asset.print_label',
            'itam.asset.view_remote_id', 'itam.asset.view_remote_credentials',

            // ITAM — licenses
            'itam.license.create', 'itam.license.assign', 'itam.license.revoke',

            // Requests
            'requests.request.create', 'requests.request.submit', 'requests.request.cancel',
            'requests.request.approve_manager', 'requests.request.approve_admin',
            'requests.request.reject', 'requests.request.fulfill', 'requests.request.view_all',

            // HR
            'hr.template.create', 'hr.template.update', 'hr.template.delete',
            'hr.onboarding.initiate', 'hr.onboarding.complete', 'hr.onboarding.cancel',
            'hr.offboarding.initiate', 'hr.offboarding.complete', 'hr.offboarding.cancel',
            'hr.process.view_all', 'hr.process.view_assigned',
            'hr.task.complete',

            // Ticketing
            'ticketing.ticket.create', 'ticketing.ticket.assign', 'ticketing.ticket.close',
            'ticketing.ticket.view_internal_notes',
            'ticketing.ticket.comment_internal', 'ticketing.ticket.comment_public',

            // Visits
            'visits.visit.create', 'visits.visit.start', 'visits.visit.close',
            'visits.visit.set_cost', 'visits.visit.checkin_offline',

            // Costing
            'costing.rate_card.manage', 'costing.travel_zone.manage', 'costing.contract.manage',

            // Email (cPanel/WHM)
            'email.server.manage', 'email.server.view_credentials',
            'email.account.create', 'email.account.reset_password',
            'email.account.suspend', 'email.account.delete',

            // System (system-admin only)
            'system.dns_provider.manage', 'system.dns_provider.view_credentials',

            // Reports
            'reports.itam.view', 'reports.ticketing.view',
            'reports.financial.view', 'reports.audit.view',
        ];
    }

    public function run(): void
    {
        // Permissions are global. Bind team to null while seeding so Spatie
        // doesn't try to scope these inserts to any org.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (self::keys() as $key) {
            Permission::query()->updateOrCreate(
                ['name' => $key, 'guard_name' => 'web'],
                []
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
