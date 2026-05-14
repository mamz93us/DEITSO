<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class HrWorkflowTemplateTask extends Model
{
    use HasTranslations;
    use HasUlids;
    use LogsActivity;

    // Task-type constants — match the type enum in the migration.
    public const TYPE_MANUAL = 'manual';

    public const TYPE_ASSIGN_ASSET = 'assign_asset';

    public const TYPE_ASSIGN_ACCESSORY = 'assign_accessory';

    public const TYPE_CREATE_EMAIL = 'create_email';

    public const TYPE_ASSIGN_LICENSE = 'assign_license';

    public const TYPE_GRANT_ACCESS = 'grant_access';

    public const TYPE_CUSTOM_ACTION = 'custom_action';

    public const TYPE_COLLECT_ASSET = 'collect_asset';

    public const TYPE_DELETE_EMAIL = 'delete_email';

    public const TYPE_SUSPEND_EMAIL = 'suspend_email';

    public const TYPE_REVOKE_LICENSE = 'revoke_license';

    public const TYPE_DISABLE_USER = 'disable_user';

    public const TYPE_DATA_BACKUP = 'data_backup';

    public const ROLE_IT_TECHNICIAN = 'it_technician';

    public const ROLE_HR = 'hr';

    public const ROLE_PROCUREMENT = 'procurement';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_REQUESTER = 'requester';

    public const ROLE_OTHER = 'other';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'template_id', 'order_index', 'title', 'description',
        'type', 'config', 'assignee_role', 'is_required', 'due_offset_days',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'config' => 'array',
            'is_required' => 'boolean',
            'due_offset_days' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrWorkflowTemplate::class, 'template_id');
    }
}
