<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current.organization')) {
            return;
        }

        $org = app('current.organization');
        if ($org === null) {
            return;
        }

        $orgId = is_object($org) ? $org->id : $org;
        $builder->where($model->getTable().'.organization_id', $orgId);
    }
}
