<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DnsProviderAccount;
use App\Models\Organization;
use App\Models\OrganizationBranding;
use App\Models\OrganizationDomain;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the demo state described in PROJECT.md Section 15 (Sprint 1):
 *
 *  - 1 system admin user (admin@platform.test)
 *  - 1 organization "SamirGroup" (slug: samirgroup)
 *  - 2 branches: Cairo (CAI), Alexandria (ALX)
 *  - 1 default DnsProviderAccount (godaddy, OTE)
 *  - 1 sample OrganizationDomain (samirgroup.it.deevar.cloud, pending)
 *  - 1 org-admin user attached to SamirGroup
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $systemAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@platform.test'],
            [
                'name' => 'Platform System Admin',
                'password' => Hash::make('password'),
                'is_system_admin' => true,
                'locale' => 'en',
                'timezone' => 'Africa/Cairo',
                'email_verified_at' => now(),
            ]
        );

        $dnsAccount = DnsProviderAccount::query()->updateOrCreate(
            ['provider' => DnsProviderAccount::PROVIDER_GODADDY, 'base_domain' => 'it.deevar.cloud'],
            [
                'name' => 'GoDaddy OTE (default)',
                'environment' => DnsProviderAccount::ENV_OTE,
                'is_default' => true,
                'status' => 'pending',
                'credentials_encrypted' => null,
            ]
        );

        $org = Organization::query()->updateOrCreate(
            ['slug' => 'samirgroup'],
            [
                'name' => ['en' => 'SamirGroup', 'ar' => 'مجموعة سمير'],
                'status' => 'active',
                'settings' => ['country' => 'EG', 'currency' => 'EGP'],
            ]
        );

        OrganizationBranding::query()->updateOrCreate(
            ['organization_id' => $org->id],
            [
                'primary_color' => '#1e3a8a',
                'secondary_color' => '#0f172a',
                'accent_color' => '#fbbf24',
                'portal_welcome_text' => ['en' => 'Welcome to SamirGroup', 'ar' => 'مرحباً بك في مجموعة سمير'],
            ]
        );

        Branch::query()->updateOrCreate(
            ['organization_id' => $org->id, 'code' => 'CAI'],
            [
                'name' => ['en' => 'Cairo HQ', 'ar' => 'المقر الرئيسي - القاهرة'],
                'address' => ['city' => 'Cairo', 'country' => 'EG'],
                'is_primary' => true,
            ]
        );

        Branch::query()->updateOrCreate(
            ['organization_id' => $org->id, 'code' => 'ALX'],
            [
                'name' => ['en' => 'Alexandria Branch', 'ar' => 'فرع الإسكندرية'],
                'address' => ['city' => 'Alexandria', 'country' => 'EG'],
                'is_primary' => false,
            ]
        );

        OrganizationDomain::query()->updateOrCreate(
            ['host' => 'samirgroup.it.deevar.cloud'],
            [
                'organization_id' => $org->id,
                'type' => OrganizationDomain::TYPE_PLATFORM,
                'dns_provider_id' => $dnsAccount->id,
                'dns_status' => OrganizationDomain::DNS_PENDING,
                'tls_status' => OrganizationDomain::TLS_PENDING,
                'is_primary' => true,
            ]
        );

        $orgAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@samirgroup.test'],
            [
                'name' => 'SamirGroup Admin',
                'password' => Hash::make('password'),
                'is_system_admin' => false,
                'locale' => 'en',
                'timezone' => 'Africa/Cairo',
                'email_verified_at' => now(),
            ]
        );

        $org->users()->syncWithoutDetaching([
            $orgAdmin->id => ['default_role' => 'Org Admin', 'joined_at' => now(), 'is_default' => true],
        ]);

        // Roles are org-scoped — seed them now that the org exists.
        (new RoleSeeder)->setContainer(app())->run();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $orgAdmin->assignRole('Org Admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Demo seed: SamirGroup org + 2 branches + 2 users provisioned.');
        $this->command?->info('System admin: admin@platform.test / password');
        $this->command?->info('Org admin:    admin@samirgroup.test / password');
    }
}
