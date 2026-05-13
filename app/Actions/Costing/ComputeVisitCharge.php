<?php

declare(strict_types=1);

namespace App\Actions\Costing;

use App\Models\RateCard;
use App\Models\TravelZone;
use App\Models\Visit;

/**
 * Computes a visit's total charge based on:
 *   - matching RateCard (visit_type + seniority + validity window)
 *   - any attached TravelZone flat fee
 *   - parts cost (sum of quantity × unit_cost_minor)
 *
 * Charge is rounded UP to the rate card's billing_increment_minutes and bounded
 * below by minimum_charge_minor.
 *
 * Returns the visit with `total_charge_minor` written. Idempotent — re-running
 * recomputes from the current data.
 */
class ComputeVisitCharge
{
    public function __invoke(Visit $visit): Visit
    {
        if ($visit->is_billable === false) {
            $visit->update(['total_charge_minor' => 0]);

            return $visit->fresh();
        }

        $rate = $this->resolveRateCard($visit);

        $labour = 0;
        if ($rate && $visit->duration_minutes) {
            $billableMinutes = $this->roundUpToIncrement(
                (int) $visit->duration_minutes,
                max(1, (int) $rate->billing_increment_minutes),
            );
            $labour = (int) round(($billableMinutes / 60) * $rate->hourly_rate_minor);
            $labour = max($labour, (int) $rate->minimum_charge_minor);
        }

        $travel = 0;
        if ($visit->travel_zone_id) {
            $zone = TravelZone::query()->withoutGlobalScopes()->find($visit->travel_zone_id);
            $travel = (int) ($zone?->flat_fee_minor ?? 0);
        }

        $parts = 0;
        foreach ($visit->parts as $p) {
            $parts += (int) $p->quantity * (int) $p->unit_cost_minor;
        }

        $visit->update(['total_charge_minor' => $labour + $travel + $parts]);

        return $visit->fresh();
    }

    protected function resolveRateCard(Visit $visit): ?RateCard
    {
        // Look for an exact match on visit_type first, then fall back to 'any'.
        $query = RateCard::query()
            ->where('organization_id', $visit->organization_id)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->orderByDesc('is_default');

        $exact = (clone $query)->where('visit_type', $visit->type)->first();
        if ($exact) {
            return $exact;
        }

        $any = (clone $query)->where('visit_type', 'any')->first();
        if ($any) {
            return $any;
        }

        return (clone $query)->where('is_default', true)->first();
    }

    protected function roundUpToIncrement(int $minutes, int $increment): int
    {
        if ($increment <= 0) {
            return $minutes;
        }

        return (int) (ceil($minutes / $increment) * $increment);
    }
}
