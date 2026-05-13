<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends BaseModel
{
    use BelongsToOrganization;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id', 'customer_organization_id', 'code', 'name',
        'included_hours_per_month', 'start_date', 'end_date', 'auto_renew', 'status',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'included_hours_per_month' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'auto_renew' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'customer_organization_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(ContractedHoursLedger::class);
    }
}
