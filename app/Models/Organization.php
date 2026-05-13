<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\OrganizationUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'settings',
        'status',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(OrganizationDomain::class);
    }

    public function primaryDomain(): HasOne
    {
        return $this->hasOne(OrganizationDomain::class)->where('is_primary', true);
    }

    public function branding(): HasOne
    {
        return $this->hasOne(OrganizationBranding::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withPivot('default_role', 'joined_at', 'is_default')
            ->withTimestamps();
    }
}
