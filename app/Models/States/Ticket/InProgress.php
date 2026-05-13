<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class InProgress extends TicketState
{
    public static $name = 'in_progress';

    public function label(): string
    {
        return 'In progress';
    }

    public function color(): string
    {
        return 'warning';
    }
}
