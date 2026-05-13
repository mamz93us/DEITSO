<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Closes the current assignment for an asset and returns it to stock. Used by
 * the manual "release" inline action and by the HR offboarding workflow's
 * collect_asset task type (Sprint 14).
 */
class RevokeAssetFromEmployee
{
    public function __invoke(Asset $asset, ?string $reason = null): void
    {
        DB::transaction(function () use ($asset, $reason) {
            AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('to_at')
                ->update([
                    'to_at' => Carbon::now(),
                    'reason' => $reason ?: 'revoked',
                ]);

            $asset->update([
                'assigned_employee_id' => null,
                'assigned_branch_id' => $asset->branch_id, // back to its home branch if set
                'status' => Asset::STATUS_IN_STOCK,
            ]);
        });
    }
}
