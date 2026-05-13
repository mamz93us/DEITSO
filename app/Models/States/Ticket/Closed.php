<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class Closed extends TicketState
{
    public static $name = 'closed';

    public function label(): string
    {
        return 'Closed';
    }

    public function color(): string
    {
        return 'success';
    }
}
