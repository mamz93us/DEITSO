<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\States\Ticket\TicketState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStates\HasStates;

class Ticket extends Model implements HasMedia
{
    use BelongsToOrganization;
    use HasStates;
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITY_CRITICAL = 'critical';

    public const SOURCE_WEB = 'web';

    public const SOURCE_PORTAL = 'portal';

    public const SOURCE_EMAIL = 'email';

    public const SOURCE_WHATSAPP = 'whatsapp';

    public const SOURCE_PHONE = 'phone';

    public const SOURCE_WALK_IN = 'walk_in';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'organization_id',
        'branch_id',
        'category_id',
        'related_asset_id',
        'related_employee_id',
        'requester_user_id',
        'assigned_user_id',
        'priority',
        'state',
        'source',
        'subject',
        'description',
        'sla_policy_id',
        'opened_at',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'paused_seconds_total',
        'paused_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => TicketState::class,
            'opened_at' => 'datetime',
            'sla_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds_total' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state', 'priority', 'assigned_user_id', 'category_id', 'first_response_at', 'resolved_at', 'closed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function relatedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'related_asset_id');
    }

    public function relatedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'related_employee_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at');
    }

    public function getIsResponseBreachedAttribute(): bool
    {
        return $this->sla_response_due_at !== null
            && $this->first_response_at === null
            && $this->sla_response_due_at->isPast();
    }

    public function getIsResolutionBreachedAttribute(): bool
    {
        return $this->sla_resolution_due_at !== null
            && $this->resolved_at === null
            && $this->sla_resolution_due_at->isPast();
    }
}
