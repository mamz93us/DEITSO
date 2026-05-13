<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\Models\States\Visit\Scheduled;
use App\Models\Visit;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates a new Visit in Scheduled state with auto-generated VST-YYYY-0001 code.
 * Validates that every FK input (branch / customer_branch / travel_zone /
 * technician user) belongs to the same organization — defence against
 * direct API mass-assignment crossing tenants.
 *
 * @param  array<string, mixed>  $data
 */
class ScheduleVisit
{
    public function __construct(private OrganizationScopedCodeGenerator $codes) {}

    public function __invoke(string $organizationId, array $data): Visit
    {
        $this->guardBranchBelongsToOrg($data['branch_id'] ?? null, $organizationId, 'branch');
        $this->guardBranchBelongsToOrg($data['customer_branch_id'] ?? null, $organizationId, 'customer branch');
        $this->guardTableBelongsToOrg('travel_zones', $data['travel_zone_id'] ?? null, $organizationId, 'travel zone');
        $this->guardUserBelongsToOrg($data['technician_user_id'] ?? null, $organizationId);

        return DB::transaction(function () use ($organizationId, $data) {
            $year = (int) now()->format('Y');
            $code = $this->codes->next(
                Visit::class,
                $organizationId,
                prefix: 'VST',
                padding: 4,
                year: $year,
                yearReset: true,
            );

            return Visit::create(array_merge($data, [
                'code' => $code,
                'organization_id' => $organizationId,
                'state' => Scheduled::class,
                'currency' => $data['currency'] ?? config('app.default_currency', 'EGP'),
            ]));
        });
    }

    protected function guardBranchBelongsToOrg(?string $id, string $orgId, string $label): void
    {
        $this->guardTableBelongsToOrg('branches', $id, $orgId, $label);
    }

    protected function guardTableBelongsToOrg(string $table, ?string $id, string $orgId, string $label): void
    {
        if (! $id) {
            return;
        }
        $exists = DB::table($table)
            ->where('id', $id)
            ->where('organization_id', $orgId)
            ->exists();
        if (! $exists) {
            throw new RuntimeException("Cross-tenant {$label} reference rejected.");
        }
    }

    protected function guardUserBelongsToOrg(?string $userId, string $orgId): void
    {
        if (! $userId) {
            return;
        }
        $isMember = DB::table('organization_user')
            ->where('user_id', $userId)
            ->where('organization_id', $orgId)
            ->exists();
        if (! $isMember) {
            throw new RuntimeException('Cross-tenant technician reference rejected.');
        }
    }
}
