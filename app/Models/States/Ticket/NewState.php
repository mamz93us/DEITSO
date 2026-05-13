<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class NewState extends TicketState
{
    public static $name = 'new';

    public function label(): string
    {
        return 'New';
    }

    public function color(): string
    {
        return 'info';
    }
}
