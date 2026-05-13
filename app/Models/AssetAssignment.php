<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * History row for an asset assignment. Polymorphic `assigned_to` so the same
 * model handles employee / branch / user holders.
 *
 * Convention: only ONE row per asset has `to_at = NULL` at any time — that's
 * the current holder. AssignAssetToEmployee enforces this invariant.
 */
class AssetAssignment extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsActivity;

    public const TYPE_EMPLOYEE = 'employee';

    public const TYPE_BRANCH = 'branch';

    public const TYPE_USER = 'user';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'organization_id',
        'assigned_to_type',
        'assigned_to_id',
        'quantity',
        'from_at',
        'to_at',
        'assigned_by_user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'from_at' => 'datetime',
            'to_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['asset_id', 'assigned_to_type', 'assigned_to_id', 'from_at', 'to_at', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Morph the holder polymorphically via assigned_to_type + assigned_to_id.
     * Returns Employee | Branch | User depending on the row.
     */
    public function holder(): MorphTo
    {
        return $this->morphTo(name: 'assigned_to', type: 'assigned_to_type', id: 'assigned_to_id');
    }

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->whereNull('to_at');
    }
}
