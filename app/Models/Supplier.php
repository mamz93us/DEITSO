<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseModel
{
    use BelongsToOrganization;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'tax_number',
        'commercial_registration_number',
        'payment_terms',
        'status',
        'notes',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'address' => 'array',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class, 'preferred_supplier_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
