<?php

declare(strict_types=1);

namespace Tests\Stubs;

use App\Models\BaseModel;
use App\Models\Concerns\BelongsToOrganization;

class OrgScopedModel extends BaseModel
{
    use BelongsToOrganization;

    protected $table = 'org_scoped_stubs';

    protected $guarded = [];

    public $timestamps = false;
}
