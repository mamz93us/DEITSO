<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends BaseModel
{
    use BelongsToOrganization;

    public const TRACKING_SERIALIZED = 'serialized';

    public const TRACKING_BULK = 'bulk';

    public const TRACKING_LICENSE = 'license';

    protected $table = 'asset_categories';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'parent_id',
        'tracking_mode',
        'custom_fields_schema',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'custom_fields_schema' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class, 'category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
