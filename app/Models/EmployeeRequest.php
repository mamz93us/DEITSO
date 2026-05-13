<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\States\EmployeeRequest\EmployeeRequestState;
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

class EmployeeRequest extends Model implements HasMedia
{
    use BelongsToOrganization;
    use HasStates;
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public const TYPE_NEW_ASSET = 'new_asset';

    public const TYPE_NEW_ACCESSORY = 'new_accessory';

    public const TYPE_UPGRADE_EXISTING = 'upgrade_existing';

    public const TYPE_NEW_LICENSE = 'new_license';

    public const TYPE_OTHER = 'other';

    public const URGENCY_LOW = 'low';

    public const URGENCY_NORMAL = 'normal';

    public const URGENCY_HIGH = 'high';

    public const URGENCY_URGENT = 'urgent';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'organization_id',
        'branch_id',
        'requester_employee_id',
        'type',
        'category_id',
        'asset_model_id',
        'related_asset_id',
        'license_name',
        'title',
        'description',
        'justification',
        'urgency',
        'estimated_cost_minor',
        'currency',
        'state',
        'manager_approval_user_id',
        'manager_approval_at',
        'manager_approval_notes',
        'admin_approval_user_id',
        'admin_approval_at',
        'admin_approval_notes',
        'assigned_procurement_user_id',
        'fulfilled_at',
        'fulfillment_asset_id',
    ];

    protected function casts(): array
    {
        return [
            'state' => EmployeeRequestState::class,
            'manager_approval_at' => 'datetime',
            'admin_approval_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'estimated_cost_minor' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'state', 'urgency', 'manager_approval_user_id', 'admin_approval_user_id',
                'assigned_procurement_user_id', 'fulfilled_at', 'fulfillment_asset_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
    }

    public function relatedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'related_asset_id');
    }

    public function fulfillmentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'fulfillment_asset_id');
    }

    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approval_user_id');
    }

    public function adminApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approval_user_id');
    }

    public function procurementAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_procurement_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EmployeeRequestComment::class)->orderBy('created_at');
    }
}
