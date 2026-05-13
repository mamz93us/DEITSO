<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

class TravelZone extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'description',
        'flat_fee_minor', 'currency', 'coverage_areas',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'flat_fee_minor' => 'integer',
            'coverage_areas' => 'array',
        ];
    }
}
