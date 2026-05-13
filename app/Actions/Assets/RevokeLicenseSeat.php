<?php

declare(strict_types=1);

namespace App\Actions\Assets;

use App\Models\AssetAssignment;
use Illuminate\Support\Carbon;

/**
 * Closes a single license seat assignment (the inverse of AssignLicenseSeat).
 * Takes the assignment row directly so the caller chooses *which* seat to
 * revoke when an employee unexpectedly holds two seats on the same license
 * (shouldn't happen — Assign blocks it — but defensive).
 */
class RevokeLicenseSeat
{
    public function __invoke(AssetAssignment $assignment, ?string $reason = null): void
    {
        if ($assignment->to_at !== null) {
            return; // already closed; idempotent
        }

        $assignment->update([
            'to_at' => Carbon::now(),
            'reason' => $reason ?: 'seat revoked',
        ]);
    }
}
