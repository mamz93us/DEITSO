<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the 10 Web v1 roles per organization (PROJECT.md Section 5).
 *
 * Each role's permission set is conservative and follows the principle of
 * least privilege. Role membership is org-scoped (team_id = organization_id),
 * which is why we iterate orgs and set the team context per loop.
 */
class RoleSeeder extends Seeder
{
    /**
     * Map of role-name → permission-key-pattern list.
     *
     * @return array<string, array<int, string>>
     */
    public static function rolePermissions(): array
    {
        // 'org.*' means "every permission starting with 'org.'".
        return [
            'Org Admin' => ['*'], // everything
            'Branch Manager' => [
                'org.branch.update',
                'employee.profile.*',
                'itam.asset.*',
                'itam.license.*',
                'requests.request.view_all', 'requests.request.fulfill',
                'hr.process.view_all', 'hr.process.view_assigned',
                'ticketing.ticket.*', 'visits.visit.*',
                'reports.itam.view', 'reports.ticketing.view',
            ],
            'Senior Technician' => [
                'itam.asset.create', 'itam.asset.update', 'itam.asset.transfer',
                'itam.asset.scrap', 'itam.asset.print_label',
                'itam.asset.view_remote_id', 'itam.asset.view_remote_credentials',
                'itam.license.assign', 'itam.license.revoke',
                'ticketing.ticket.*',
                'visits.visit.*',
                'email.account.create', 'email.account.reset_password',
                'reports.itam.view', 'reports.ticketing.view',
            ],
            'Technician' => [
                'itam.asset.update', 'itam.asset.print_label',
                'itam.asset.view_remote_id',
                'ticketing.ticket.create', 'ticketing.ticket.assign',
                'ticketing.ticket.close', 'ticketing.ticket.comment_public',
                'ticketing.ticket.comment_internal',
                'visits.visit.create', 'visits.visit.start',
                'visits.visit.close', 'visits.visit.checkin_offline',
                'hr.task.complete',
            ],
            'HR / Asset Coordinator' => [
                'employee.profile.*',
                'itam.asset.transfer', 'itam.asset.update',
                'itam.license.assign', 'itam.license.revoke',
                'requests.request.view_all', 'requests.request.fulfill',
                'hr.template.*', 'hr.onboarding.*', 'hr.offboarding.*',
                'hr.process.view_all', 'hr.task.complete',
                'email.account.create',
            ],
            'Procurement Officer' => [
                'itam.supplier.*',
                'itam.asset.create',
                'requests.request.fulfill', 'requests.request.view_all',
            ],
            'Accountant' => [
                'reports.itam.view', 'reports.ticketing.view',
                'reports.financial.view', 'reports.audit.view',
            ],
            'Manager' => [
                'requests.request.approve_manager',
                'hr.offboarding.initiate',
                'hr.process.view_assigned',
            ],
            'End User' => [
                'requests.request.create', 'requests.request.submit',
                'requests.request.cancel',
                'ticketing.ticket.create', 'ticketing.ticket.comment_public',
            ],
            // System Admin is a flag on the User row (is_system_admin), not a role.
        ];
    }

    public function run(): void
    {
        $allPermissions = PermissionSeeder::keys();

        Organization::query()->withoutGlobalScopes()->each(function (Organization $org) use ($allPermissions) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

            foreach (self::rolePermissions() as $roleName => $patterns) {
                $role = Role::query()->updateOrCreate(
                    ['name' => $roleName, 'guard_name' => 'web', 'team_id' => $org->id],
                    []
                );

                $perms = $this->expandPatterns($patterns, $allPermissions);
                $role->syncPermissions($perms);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $patterns
     * @param  array<int, string>  $allPermissions
     * @return array<int, string>
     */
    protected function expandPatterns(array $patterns, array $allPermissions): array
    {
        $matched = [];
        foreach ($patterns as $p) {
            if ($p === '*') {
                return $allPermissions;
            }
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                foreach ($allPermissions as $perm) {
                    if (str_starts_with($perm, $prefix.'.')) {
                        $matched[] = $perm;
                    }
                }
            } else {
                $matched[] = $p;
            }
        }

        return array_values(array_unique($matched));
    }
}
