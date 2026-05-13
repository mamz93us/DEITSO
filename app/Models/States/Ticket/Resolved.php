<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class Resolved extends TicketState
{
    public static $name = 'resolved';

    public function label(): string
    {
        return 'Resolved';
    }

    public function color(): string
    {
        return 'success';
    }
}
