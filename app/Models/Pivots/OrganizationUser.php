<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    public $incrementing = false;

    protected $casts = [
        'joined_at' => 'datetime',
        'is_default' => 'boolean',
    ];
}
