<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\States\Visit\VisitState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStates\HasStates;

class Visit extends Model implements HasMedia
{
    use BelongsToOrganization;
    use HasStates;
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public const TYPE_ONLINE = 'online';

    public const TYPE_OFFLINE = 'offline';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'organization_id',
        'branch_id',
        'customer_branch_id',
        'type',
        'state',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'technician_user_id',
        'additional_technicians',
        'checkin_lat',
        'checkin_lng',
        'checkin_at',
        'checkout_lat',
        'checkout_lng',
        'checkout_at',
        'travel_zone_id',
        'summary',
        'internal_notes',
        'total_charge_minor',
        'currency',
        'is_billable',
    ];

    protected function casts(): array
    {
        return [
            'state' => VisitState::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'checkin_at' => 'datetime',
            'checkout_at' => 'datetime',
            'duration_minutes' => 'integer',
            'total_charge_minor' => 'integer',
            'is_billable' => 'boolean',
            'additional_technicians' => 'array',
            'checkin_lat' => 'decimal:7',
            'checkin_lng' => 'decimal:7',
            'checkout_lat' => 'decimal:7',
            'checkout_lng' => 'decimal:7',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state', 'started_at', 'ended_at', 'total_charge_minor', 'technician_user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('signatures')->singleFile();
        $this->addMediaCollection('attachments');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function customerBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'customer_branch_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(VisitPart::class);
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'visit_ticket');
    }
}
