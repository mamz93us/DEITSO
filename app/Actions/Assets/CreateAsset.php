<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Organization;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates an Asset with an auto-generated, org-scoped code.
 *
 * Code template: {ORG_PREFIX}-{BRANCH_PREFIX}-{CATEGORY_PREFIX}-{0001}
 *   e.g. SMR-CAI-LAP-0001
 *
 * Prefixes are derived from existing codes:
 *   - ORG_PREFIX = first 3 upper-cased letters of org.slug
 *   - BRANCH_PREFIX = branch.code (or "GEN" if no branch supplied)
 *   - CATEGORY_PREFIX = category.code
 *
 * The sequence resets per (org, branch, category) tuple. Soft-deleted assets
 * are *included* when computing max(seq) so codes don't collide with
 * historical rows held by the unique constraint.
 */
class CreateAsset
{
    public function __construct(
        private OrganizationScopedCodeGenerator $codes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(string $organizationId, array $data): Asset
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $org = Organization::withoutGlobalScopes()->findOrFail($organizationId);
            $category = AssetCategory::findOrFail($data['category_id']);
            $branch = ! empty($data['branch_id']) ? Branch::find($data['branch_id']) : null;

            $orgPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $org->slug), 0, 3) ?: 'ORG');
            $branchPrefix = $branch?->code ?: 'GEN';
            $catPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $category->code));

            $code = $this->codes->next(
                Asset::class,
                $organizationId,
                prefix: $orgPrefix,
                padding: 4,
                extras: [
                    'BRANCH' => $branchPrefix,
                    'CATEGORY' => $catPrefix,
                ],
            );

            return Asset::create(array_merge($data, [
                'organization_id' => $organizationId,
                'code' => $code,
                'tracking_mode' => $data['tracking_mode']
                    ?? $category->tracking_mode
                    ?? AssetCategory::TRACKING_SERIALIZED,
                'status' => $data['status'] ?? Asset::STATUS_IN_STOCK,
                'currency' => $data['currency'] ?? config('app.default_currency', 'EGP'),
                'quantity' => $data['quantity'] ?? 1,
            ]));
        });
    }
}
