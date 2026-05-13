<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\Models\States\Visit\InProgress;
use App\Models\States\Visit\Scheduled;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records the technician's on-site check-in: GPS coordinates + timestamp.
 * Transitions Scheduled → InProgress, sets started_at if not already set.
 */
class CheckInVisit
{
    public function __invoke(Visit $visit, ?float $lat = null, ?float $lng = null): Visit
    {
        if (! ($visit->state instanceof Scheduled || $visit->state instanceof InProgress)) {
            throw new RuntimeException('Visit must be Scheduled or InProgress to check in.');
        }

        return DB::transaction(function () use ($visit, $lat, $lng) {
            if ($visit->state instanceof Scheduled) {
                $visit->state->transitionTo(InProgress::class);
            }

            $visit->update([
                'checkin_lat' => $lat,
                'checkin_lng' => $lng,
                'checkin_at' => now(),
                'started_at' => $visit->started_at ?? now(),
            ]);

            return $visit->fresh();
        });
    }
}
