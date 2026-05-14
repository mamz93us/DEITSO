<?php

declare(strict_types=1);

namespace App\Actions\Costing;

use App\Models\Contract;
use App\Models\ContractedHoursLedger;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Records a ledger entry that consumes contract hours for a visit.
 *
 * Used after a visit completes to "deduct" billable hours from the customer's
 * monthly contract bucket. Idempotent per (contract, visit) — re-running for
 * the same visit updates the existing entry rather than double-billing.
 *
 * Hours are derived from visit.duration_minutes (rounded up to nearest 0.25h),
 * but the caller can override via $hoursOverride when only a portion should
 * be billed against the contract (e.g. mixed-bill visit).
 */
class ConsumeContractHours
{
    public function __invoke(Contract $contract, Visit $visit, ?float $hoursOverride = null): ContractedHoursLedger
    {
        return DB::transaction(function () use ($contract, $visit, $hoursOverride) {
            $hours = $hoursOverride !== null
                ? round($hoursOverride, 2)
                : $this->minutesToHours((int) ($visit->duration_minutes ?? 0));

            $reference = $visit->ended_at ?? $visit->started_at ?? now();
            $year = (int) $reference->format('Y');
            $month = (int) $reference->format('n');

            return ContractedHoursLedger::updateOrCreate(
                ['contract_id' => $contract->id, 'visit_id' => $visit->id],
                [
                    'period_year' => $year,
                    'period_month' => $month,
                    'hours_consumed' => $hours,
                ],
            );
        });
    }

    /**
     * Round minutes up to the nearest 0.25-hour quarter.
     */
    protected function minutesToHours(int $minutes): float
    {
        if ($minutes <= 0) {
            return 0.0;
        }

        $quarterHours = (int) ceil($minutes / 15);

        return round($quarterHours * 0.25, 2);
    }

    /**
     * Returns hours remaining in the current period for the contract
     * (included_hours_per_month minus sum of ledger entries this month).
     */
    public function hoursRemainingThisMonth(Contract $contract): float
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');

        $consumed = (float) ContractedHoursLedger::query()
            ->where('contract_id', $contract->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('hours_consumed');

        return max(0.0, (float) $contract->included_hours_per_month - $consumed);
    }
}
