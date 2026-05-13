<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Assigns an asset to an employee. Closes any open assignment, opens a new
 * one with to_at = NULL, and updates Asset's denormalized
 * assigned_employee_id + assigned_branch_id pointers.
 *
 * Invariant: at most ONE asset_assignment row per asset has to_at = NULL.
 */
class AssignAssetToEmployee
{
    public function __invoke(Asset $asset, Employee $employee, ?string $reason = null): AssetAssignment
    {
        if ($asset->organization_id !== $employee->organization_id) {
            throw new RuntimeException('Cross-tenant assignment is not allowed.');
        }

        if (in_array($asset->status, [Asset::STATUS_SCRAPPED, Asset::STATUS_LOST], true)) {
            throw new RuntimeException('Cannot assign a scrapped or lost asset.');
        }

        return DB::transaction(function () use ($asset, $employee, $reason) {
            // Close any open assignment first.
            AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('to_at')
                ->update(['to_at' => Carbon::now()]);

            $assignment = AssetAssignment::create([
                'asset_id' => $asset->id,
                'organization_id' => $asset->organization_id,
                'assigned_to_type' => AssetAssignment::TYPE_EMPLOYEE,
                'assigned_to_id' => $employee->id,
                'quantity' => max(1, (int) $asset->quantity),
                'from_at' => Carbon::now(),
                'assigned_by_user_id' => Auth::id(),
                'reason' => $reason,
            ]);

            // Update denormalized shortcuts on the asset.
            $asset->update([
                'assigned_employee_id' => $employee->id,
                'assigned_branch_id' => $employee->branch_id,
                'status' => Asset::STATUS_DEPLOYED,
            ]);

            return $assignment;
        });
    }
}
