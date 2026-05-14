<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Organization;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Whenever a new Organization is created, immediately seed its per-org roles
 * so the first user added to it can be granted Org Admin, End User, etc.
 *
 * Without this, creating an org via the UI/API leaves it with zero roles
 * until someone manually re-runs RoleSeeder. The seeder is org-aware
 * (iterates all orgs and sets team context per loop), so it's safe to call
 * on a single new org — it'll skip existing role rows.
 */
class OrganizationObserver
{
    public function created(Organization $organization): void
    {
        try {
            (new RoleSeeder)->setContainer(app())->run();
        } catch (Throwable $e) {
            // Seeding failure should never block org creation. Log and move on.
            Log::warning('OrganizationObserver: RoleSeeder failed', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
