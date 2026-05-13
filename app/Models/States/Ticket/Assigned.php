<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class Assigned extends TicketState
{
    public static $name = 'assigned';

    public function label(): string
    {
        return 'Assigned';
    }

    public function color(): string
    {
        return 'primary';
    }
}
