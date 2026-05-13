<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EmployeeRequestComment extends Model implements HasMedia
{
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;

    public const AUTHOR_REQUESTER = 'requester';

    public const AUTHOR_MANAGER = 'manager';

    public const AUTHOR_ADMIN = 'admin';

    public const AUTHOR_PROCUREMENT = 'procurement';

    public const AUTHOR_SYSTEM = 'system';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'employee_request_id',
        'user_id',
        'author_role',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(EmployeeRequest::class, 'employee_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
