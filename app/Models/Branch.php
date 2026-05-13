<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

class Branch extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'address',
        'lat',
        'lng',
        'is_primary',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_primary' => 'boolean',
        ];
    }
}
