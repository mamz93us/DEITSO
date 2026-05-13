<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetTransfer;
use App\Models\States\AssetTransfer\Completed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates a new AssetTransfer in Draft state. The state can then be
 * transitioned via $transfer->state->transitionTo(...) by approvers.
 *
 * Calling `complete()` performs the side-effects: closes the open assignment
 * and opens a new one for the destination, then sets the asset's denormalized
 * pointers and transitions the transfer state to Completed.
 *
 * @param  array<string, mixed>  $data
 */
class TransferAsset
{
    public function create(Asset $asset, array $data): AssetTransfer
    {
        $this->guardSameOrg('employees', $data['to_employee_id'] ?? null, $asset->organization_id, 'destination employee');
        $this->guardSameOrg('branches', $data['to_branch_id'] ?? null, $asset->organization_id, 'destination branch');

        return AssetTransfer::create([
            'asset_id' => $asset->id,
            'organization_id' => $asset->organization_id,
            'branch_id' => $asset->branch_id,
            'from_employee_id' => $asset->assigned_employee_id,
            'to_employee_id' => $data['to_employee_id'] ?? null,
            'from_branch_id' => $asset->assigned_branch_id,
            'to_branch_id' => $data['to_branch_id'] ?? null,
            'requested_by_user_id' => Auth::id() ?? $data['requested_by_user_id'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function complete(AssetTransfer $transfer, ?string $approvedByUserId = null): void
    {
        DB::transaction(function () use ($transfer, $approvedByUserId) {
            $asset = $transfer->asset;

            // Close the currently-open assignment.
            AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('to_at')
                ->update(['to_at' => Carbon::now()]);

            // Open a new one if there's a destination holder.
            if ($transfer->to_employee_id || $transfer->to_branch_id) {
                AssetAssignment::create([
                    'asset_id' => $asset->id,
                    'organization_id' => $asset->organization_id,
                    'assigned_to_type' => $transfer->to_employee_id
                        ? AssetAssignment::TYPE_EMPLOYEE
                        : AssetAssignment::TYPE_BRANCH,
                    'assigned_to_id' => $transfer->to_employee_id ?? $transfer->to_branch_id,
                    'quantity' => max(1, (int) $asset->quantity),
                    'from_at' => Carbon::now(),
                    'assigned_by_user_id' => $approvedByUserId ?? Auth::id(),
                    'reason' => $transfer->reason,
                ]);
            }

            $asset->update([
                'assigned_employee_id' => $transfer->to_employee_id,
                'assigned_branch_id' => $transfer->to_branch_id,
                'status' => $transfer->to_employee_id || $transfer->to_branch_id
                    ? Asset::STATUS_DEPLOYED
                    : Asset::STATUS_IN_STOCK,
            ]);

            $transfer->state->transitionTo(Completed::class);
            $transfer->update([
                'approved_by_user_id' => $approvedByUserId ?? Auth::id(),
                'transferred_at' => Carbon::now(),
            ]);
        });
    }

    protected function guardSameOrg(string $table, ?string $id, string $orgId, string $label): void
    {
        if (! $id) {
            return;
        }
        $ok = DB::table($table)
            ->where('id', $id)
            ->where('organization_id', $orgId)
            ->exists();
        if (! $ok) {
            throw new RuntimeException("Cross-tenant {$label} reference rejected.");
        }
    }
}
