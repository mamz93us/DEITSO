<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\States\Ticket\Assigned;
use App\Models\States\Ticket\NewState;
use App\Models\Ticket;
use App\Models\User;

class AssignTicket
{
    public function __invoke(Ticket $ticket, User $assignee): Ticket
    {
        $ticket->update(['assigned_user_id' => $assignee->id]);

        if ($ticket->state instanceof NewState) {
            $ticket->state->transitionTo(Assigned::class);
        }

        return $ticket->fresh();
    }
}
