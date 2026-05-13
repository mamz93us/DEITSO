<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_DEPLOYED = 'deployed';

    public const STATUS_IN_MAINTENANCE = 'in_maintenance';

    public const STATUS_RETIRED = 'retired';

    public const STATUS_SCRAPPED = 'scrapped';

    public const STATUS_LOST = 'lost';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'asset_model_id',
        'category_id',
        'code',
        'serial_number',
        'name',
        'status',
        'tracking_mode',
        'quantity',
        'assigned_employee_id',
        'assigned_branch_id',
        'supplier_id',
        'purchase_order_reference',
        'source_request_id',
        'device_id',
        'purchase_date',
        'purchase_cost_minor',
        'currency',
        'vendor',
        'warranty_until',
        'depreciation_method',
        'depreciation_months',
        'custom_fields',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_until' => 'date',
            'custom_fields' => 'array',
            'quantity' => 'integer',
            'purchase_cost_minor' => 'integer',
            'depreciation_months' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'tracking_mode', 'quantity', 'assigned_employee_id',
                'assigned_branch_id', 'supplier_id', 'purchase_cost_minor',
                'warranty_until', 'name', 'serial_number',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function assignedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)->latest('from_at');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('to_at')->latestOfMany('from_at');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class)->latest();
    }

    public function scraps(): HasMany
    {
        return $this->hasMany(AssetScrap::class);
    }

    public function remoteAccess(): HasMany
    {
        return $this->hasMany(AssetRemoteAccess::class);
    }
}
