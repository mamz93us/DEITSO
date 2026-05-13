<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractedHoursLedger extends Model
{
    use HasUlids;

    protected $table = 'contracted_hours_ledger';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'contract_id', 'visit_id', 'period_year', 'period_month', 'hours_consumed',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'hours_consumed' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
