<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrWorkflowTemplate extends BaseModel
{
    use BelongsToOrganization;

    public const TYPE_ONBOARDING = 'onboarding';

    public const TYPE_OFFBOARDING = 'offboarding';

    protected $fillable = [
        'organization_id', 'type', 'name', 'description',
        'department_id', 'position_tag', 'is_default', 'is_active',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HrWorkflowTemplateTask::class, 'template_id')
            ->orderBy('order_index');
    }
}
