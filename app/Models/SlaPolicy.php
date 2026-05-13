<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

class SlaPolicy extends BaseModel
{
    use BelongsToOrganization;

    protected $table = 'sla_policies';

    protected $fillable = [
        'organization_id', 'name', 'priority', 'response_minutes',
        'resolution_minutes', 'business_hours_only', 'business_hours', 'is_default',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
            'business_hours_only' => 'boolean',
            'business_hours' => 'array',
            'is_default' => 'boolean',
        ];
    }
}
