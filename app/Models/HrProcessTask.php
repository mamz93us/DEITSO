<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\States\HrProcessTask\HrProcessTaskState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

class HrProcessTask extends Model
{
    use HasStates;
    use HasTranslations;
    use HasUlids;
    use LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'hr_process_id', 'order_index', 'title', 'description',
        'type', 'config', 'assigned_to_user_id', 'state', 'result',
        'linked_request_id', 'due_date', 'completed_at',
        'completed_by_user_id', 'notes',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'state' => HrProcessTaskState::class,
            'config' => 'array',
            'result' => 'array',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'order_index' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state', 'assigned_to_user_id', 'completed_at', 'result', 'linked_request_id'])
            ->logOnlyDirty();
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(HrProcess::class, 'hr_process_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function linkedRequest(): BelongsTo
    {
        return $this->belongsTo(EmployeeRequest::class, 'linked_request_id');
    }
}
