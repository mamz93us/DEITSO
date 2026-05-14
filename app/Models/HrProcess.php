<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\States\HrProcess\HrProcessState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

class HrProcess extends Model
{
    use BelongsToOrganization;
    use HasStates;
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const TYPE_ONBOARDING = 'onboarding';

    public const TYPE_OFFBOARDING = 'offboarding';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code', 'organization_id', 'branch_id', 'type',
        'employee_id', 'template_id', 'initiated_by_user_id',
        'target_date', 'state', 'completed_at', 'completed_by_user_id',
        'handover_pdf_path', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'state' => HrProcessState::class,
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state', 'target_date', 'completed_at', 'completed_by_user_id', 'handover_pdf_path'])
            ->logOnlyDirty();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrWorkflowTemplate::class, 'template_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HrProcessTask::class)->orderBy('order_index');
    }
}
