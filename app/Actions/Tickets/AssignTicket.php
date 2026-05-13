<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\States\Ticket\Assigned;
use App\Models\States\Ticket\NewState;
use App\Models\Ticket;
use App\Models\User;
use RuntimeException;

class AssignTicket
{
    public function __invoke(Ticket $ticket, User $assignee): Ticket
    {
        // Cross-tenant guard: assignee must be a member of the ticket's org
        // (or a system admin).
        $isAuthorized = ($assignee->is_system_admin ?? false) === true
            || $assignee->organizations()->where('organizations.id', $ticket->organization_id)->exists();
        if (! $isAuthorized) {
            throw new RuntimeException('Cannot assign a ticket to a user outside its organization.');
        }

        $ticket->update(['assigned_user_id' => $assignee->id]);

        if ($ticket->state instanceof NewState) {
            $ticket->state->transitionTo(Assigned::class);
        }

        return $ticket->fresh();
    }
}
