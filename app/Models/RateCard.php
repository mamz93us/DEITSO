<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

class RateCard extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'visit_type', 'technician_seniority',
        'hourly_rate_minor', 'currency', 'minimum_charge_minor',
        'billing_increment_minutes', 'is_default', 'valid_from', 'valid_to',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'hourly_rate_minor' => 'integer',
            'minimum_charge_minor' => 'integer',
            'billing_increment_minutes' => 'integer',
            'is_default' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }
}
