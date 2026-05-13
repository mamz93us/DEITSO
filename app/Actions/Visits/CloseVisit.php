<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\Actions\Costing\ComputeVisitCharge;
use App\Models\States\Visit\Completed;
use App\Models\States\Visit\InProgress;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Closes an in-progress visit: records checkout GPS + timestamp, computes
 * duration_minutes, transitions to Completed.
 *
 * The visit's `total_charge_minor` is left as-is — costing logic lives in
 * Sprint 11's ComputeVisitCharge which is invoked from here once the rate
 * card + travel-zone machinery exists. For now we just set the visit
 * to Completed and hand off to ComputeVisitCharge if it's available.
 */
class CloseVisit
{
    public function __invoke(Visit $visit, ?float $lat = null, ?float $lng = null, ?string $summary = null): Visit
    {
        if (! $visit->state instanceof InProgress) {
            throw new RuntimeException('Only an InProgress visit can be closed.');
        }

        return DB::transaction(function () use ($visit, $lat, $lng, $summary) {
            $now = now();
            $startedAt = $visit->started_at ?? $visit->checkin_at ?? $now;
            $duration = (int) round($startedAt->diffInSeconds($now) / 60);

            $visit->update([
                'checkout_lat' => $lat,
                'checkout_lng' => $lng,
                'checkout_at' => $now,
                'ended_at' => $now,
                'duration_minutes' => max(0, $duration),
                'summary' => $summary ?? $visit->summary,
            ]);

            $visit->state->transitionTo(Completed::class);

            // Sprint 11: ComputeVisitCharge runs here once rate cards exist.
            // Deferring the chained call until that class is present so we
            // don't blow up if the costing layer isn't loaded yet.
            if (class_exists(ComputeVisitCharge::class)) {
                app(ComputeVisitCharge::class)($visit->fresh());
            }

            return $visit->fresh();
        });
    }
}
