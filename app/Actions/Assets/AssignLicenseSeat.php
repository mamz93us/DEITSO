<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Assigns a single license seat to an employee.
 *
 * Unlike serialized assets where a single open assignment is the invariant,
 * licenses can have many open assignments (one per seat). This action:
 *   - validates the license is not expired, not over-seated, and same-tenant
 *   - opens a new AssetAssignment row with the employee as holder
 *   - does NOT close prior assignments (each seat is independent)
 *
 * seats_total is enforced softly: if already at capacity, throws.
 */
class AssignLicenseSeat
{
    public function __invoke(Asset $license, Employee $employee, ?string $reason = null): AssetAssignment
    {
        if ($license->tracking_mode !== AssetCategory::TRACKING_LICENSE) {
            throw new RuntimeException('AssignLicenseSeat is only valid for license-tracked assets.');
        }

        if ($license->organization_id !== $employee->organization_id) {
            throw new RuntimeException('Cross-tenant license assignment is not allowed.');
        }

        if ($license->is_expired) {
            throw new RuntimeException('Cannot assign a seat on an expired license.');
        }

        // Re-fetch seats_used inside the transaction to avoid races.
        return DB::transaction(function () use ($license, $employee, $reason) {
            $used = AssetAssignment::query()
                ->where('asset_id', $license->id)
                ->whereNull('to_at')
                ->count();

            if ($license->seats_total !== null && $used >= $license->seats_total) {
                throw new RuntimeException(sprintf(
                    'License has no seats available (%d / %d in use).',
                    $used,
                    $license->seats_total,
                ));
            }

            // Already assigned to this employee? No double-assignment.
            $exists = AssetAssignment::query()
                ->where('asset_id', $license->id)
                ->whereNull('to_at')
                ->where('assigned_to_type', AssetAssignment::TYPE_EMPLOYEE)
                ->where('assigned_to_id', $employee->id)
                ->exists();

            if ($exists) {
                throw new RuntimeException('Employee already holds a seat on this license.');
            }

            return AssetAssignment::create([
                'asset_id' => $license->id,
                'organization_id' => $license->organization_id,
                'assigned_to_type' => AssetAssignment::TYPE_EMPLOYEE,
                'assigned_to_id' => $employee->id,
                'quantity' => 1,
                'from_at' => Carbon::now(),
                'assigned_by_user_id' => Auth::id(),
                'reason' => $reason,
            ]);
        });
    }
}
