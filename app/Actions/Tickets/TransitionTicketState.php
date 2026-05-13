<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\States\Ticket\Closed;
use App\Models\States\Ticket\InProgress;
use App\Models\States\Ticket\Resolved;
use App\Models\States\Ticket\TicketState;
use App\Models\States\Ticket\WaitingCustomer;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Centralized state-transition action that also handles SLA pause / resume
 * arithmetic + resolved / closed timestamps. Always call this instead of
 * `$ticket->state->transitionTo(...)` directly so the SLA invariants stay
 * consistent.
 *
 * @param  class-string<TicketState>  $targetState
 */
class TransitionTicketState
{
    public function __invoke(Ticket $ticket, string $targetState): Ticket
    {
        return DB::transaction(function () use ($ticket, $targetState) {
            $wasPaused = $ticket->state instanceof WaitingCustomer;
            $becomesWaiting = $targetState === WaitingCustomer::class;

            // Leaving WaitingCustomer: extend SLA dates by the pause duration.
            if ($wasPaused && ! $becomesWaiting && $ticket->paused_at) {
                $pausedSeconds = $ticket->paused_at->diffInSeconds(Carbon::now());
                $newTotal = (int) $ticket->paused_seconds_total + $pausedSeconds;
                $ticket->update([
                    'paused_seconds_total' => $newTotal,
                    'paused_at' => null,
                    'sla_response_due_at' => $ticket->sla_response_due_at?->addSeconds($pausedSeconds),
                    'sla_resolution_due_at' => $ticket->sla_resolution_due_at?->addSeconds($pausedSeconds),
                ]);
            }

            // Entering WaitingCustomer: record pause start.
            if ($becomesWaiting && ! $wasPaused) {
                $ticket->update(['paused_at' => Carbon::now()]);
            }

            $ticket->state->transitionTo($targetState);

            // Side-effect timestamps for Resolved / Closed.
            if ($targetState === Resolved::class) {
                $ticket->update(['resolved_at' => Carbon::now()]);
            }
            if ($targetState === Closed::class) {
                $ticket->update(['closed_at' => Carbon::now()]);
            }

            // Reopening from Closed/Resolved → InProgress: clear closed_at / resolved_at.
            if ($targetState === InProgress::class) {
                $ticket->update(['resolved_at' => null, 'closed_at' => null]);
            }

            return $ticket->fresh();
        });
    }
}
