<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class TicketState extends State
{
    public static function config(): StateConfig
    {
        $cfg = parent::config()->default(NewState::class);

        // From New
        $cfg->allowTransition(NewState::class, Assigned::class);
        $cfg->allowTransition(NewState::class, Cancelled::class);

        // From Assigned
        $cfg->allowTransition(Assigned::class, InProgress::class);
        $cfg->allowTransition(Assigned::class, WaitingCustomer::class);
        $cfg->allowTransition(Assigned::class, Resolved::class);
        $cfg->allowTransition(Assigned::class, Cancelled::class);

        // From InProgress
        $cfg->allowTransition(InProgress::class, WaitingCustomer::class);
        $cfg->allowTransition(InProgress::class, Resolved::class);
        $cfg->allowTransition(InProgress::class, Cancelled::class);

        // From WaitingCustomer
        $cfg->allowTransition(WaitingCustomer::class, InProgress::class);
        $cfg->allowTransition(WaitingCustomer::class, Resolved::class);
        $cfg->allowTransition(WaitingCustomer::class, Cancelled::class);

        // From Resolved
        $cfg->allowTransition(Resolved::class, Closed::class);
        $cfg->allowTransition(Resolved::class, InProgress::class); // re-open

        // From Closed
        $cfg->allowTransition(Closed::class, InProgress::class); // re-open

        return $cfg;
    }

    abstract public function label(): string;

    abstract public function color(): string;
}
