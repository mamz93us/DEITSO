<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetScrap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Marks an asset scrapped. Closes any open assignment, records the disposal
 * details + residual value, and (optionally) takes a user as approver.
 *
 * Evidence (photos, certificates) is attached separately on the AssetScrap
 * row's medialibrary "evidence" collection — the Filament resource handles
 * that upload at the UI layer.
 *
 * @param  array<string, mixed>  $data
 */
class ScrapAsset
{
    public function __invoke(Asset $asset, array $data): AssetScrap
    {
        if ($asset->status === Asset::STATUS_SCRAPPED) {
            throw new RuntimeException('Asset is already scrapped.');
        }

        return DB::transaction(function () use ($asset, $data) {
            // Close any open assignment.
            AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('to_at')
                ->update(['to_at' => Carbon::now(), 'reason' => 'asset scrapped']);

            $scrap = AssetScrap::create([
                'asset_id' => $asset->id,
                'organization_id' => $asset->organization_id,
                'reason' => $data['reason'] ?? AssetScrap::REASON_END_OF_LIFE,
                'disposal_method' => $data['disposal_method'] ?? null,
                'residual_value_minor' => $data['residual_value_minor'] ?? null,
                'currency' => $data['currency'] ?? $asset->currency ?? 'EGP',
                'approved_by_user_id' => Auth::id(),
                'approved_at' => Carbon::now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $asset->update([
                'status' => Asset::STATUS_SCRAPPED,
                'assigned_employee_id' => null,
                'assigned_branch_id' => null,
            ]);

            return $scrap;
        });
    }
}
