<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class Cancelled extends TicketState
{
    public static $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }

    public function color(): string
    {
        return 'gray';
    }
}
