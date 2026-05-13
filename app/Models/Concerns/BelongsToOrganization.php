<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToOrganization
 *
 * Applies the OrganizationScope global scope (filters by app('current.organization')
 * when bound) and auto-fills organization_id on create from the same singleton.
 *
 * System context — when current.organization is unbound or null, the scope is
 * skipped so System Admin queries can span all organizations.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model): void {
            if (! $model->organization_id && app()->bound('current.organization')) {
                $org = app('current.organization');
                if ($org !== null) {
                    $model->organization_id = is_object($org) ? $org->id : $org;
                }
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
