<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmailAccount extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DELETED = 'deleted';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'email_domain_id', 'organization_id', 'local_part', 'full_address',
        'quota_mb', 'assigned_employee_id', 'status', 'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'quota_mb' => 'integer',
            'last_sync_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'quota_mb', 'assigned_employee_id'])
            ->logOnlyDirty();
    }

    public function emailDomain(): BelongsTo
    {
        return $this->belongsTo(EmailDomain::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }
}
