<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\States\AssetTransfer\AssetTransferState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

/**
 * Optional approval workflow for moving an asset between holders.
 *
 * Lifecycle (spatie/laravel-model-states):
 *   Draft → Pending → Approved → Completed
 *                  → Rejected (terminal)
 *   * → Cancelled (until Completed)
 *
 * The state column persists the FQN of the active state class; transitions
 * happen via $transfer->state->transitionTo(NextState::class).
 */
class AssetTransfer extends Model
{
    use BelongsToOrganization;
    use HasStates;
    use HasUlids;
    use LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'organization_id',
        'branch_id',
        'from_employee_id',
        'to_employee_id',
        'from_branch_id',
        'to_branch_id',
        'requested_by_user_id',
        'approved_by_user_id',
        'state',
        'reason',
        'notes',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => AssetTransferState::class,
            'transferred_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state', 'from_employee_id', 'to_employee_id', 'from_branch_id', 'to_branch_id', 'transferred_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_employee_id');
    }

    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_employee_id');
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
