<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Scopes\OrganizationScope;
use App\Models\States\Ticket\Cancelled;
use App\Models\States\Ticket\Closed;
use App\Models\States\Ticket\Resolved;
use App\Models\Ticket;
use App\Notifications\TicketSlaBreached;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

/**
 * Detects tickets whose SLA response or resolution deadlines have passed and
 * the corresponding milestone hasn't been hit yet. Notifies the assignee +
 * requester once per breach type per ticket (de-duped via the activity log).
 *
 * Schedule every 5 minutes. Cheap query — bounded by ticket count per org.
 */
class CheckTicketSlaBreaches extends Command
{
    protected $signature = 'tickets:check-sla-breaches {--dry-run}';

    protected $description = 'Notify when tickets breach SLA response or resolution deadlines.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = now();
        $stats = ['response' => 0, 'resolution' => 0];

        $tickets = Ticket::query()
            ->withoutGlobalScopes([OrganizationScope::class])
            ->whereNotIn('state', [Resolved::class, Closed::class, Cancelled::class])
            ->where(function ($q) use ($now) {
                $q->where(fn ($q) => $q->whereNull('first_response_at')->where('sla_response_due_at', '<', $now))
                    ->orWhere(fn ($q) => $q->whereNull('resolved_at')->where('sla_resolution_due_at', '<', $now));
            })
            ->with(['assignee', 'requester'])
            ->cursor();

        foreach ($tickets as $t) {
            // Response breach: first_response_at NULL + sla_response_due_at past.
            if ($t->is_response_breached && ! $this->alreadyNotified($t->id, 'response')) {
                $stats['response']++;
                if (! $dryRun) {
                    $this->dispatch($t, 'response');
                }
            }

            // Resolution breach: resolved_at NULL + sla_resolution_due_at past.
            if ($t->is_resolution_breached && ! $this->alreadyNotified($t->id, 'resolution')) {
                $stats['resolution']++;
                if (! $dryRun) {
                    $this->dispatch($t, 'resolution');
                }
            }
        }

        $this->info(sprintf(
            'SLA check done. response=%d resolution=%d%s',
            $stats['response'],
            $stats['resolution'],
            $dryRun ? ' (dry run)' : '',
        ));

        return self::SUCCESS;
    }

    protected function dispatch(Ticket $ticket, string $type): void
    {
        $recipients = collect([$ticket->assignee, $ticket->requester])->filter()->unique('id');
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketSlaBreached($ticket, $type));
        }

        // Audit row to de-dup future runs.
        activity()
            ->performedOn($ticket)
            ->event('sla_breach.'.$type)
            ->log("SLA {$type} breach notified");
    }

    protected function alreadyNotified(string $ticketId, string $type): bool
    {
        return Activity::query()
            ->where('subject_type', Ticket::class)
            ->where('subject_id', $ticketId)
            ->where('event', 'sla_breach.'.$type)
            ->exists();
    }
}
