<?php

declare(strict_types=1);

namespace App\Models\States\Ticket;

class WaitingCustomer extends TicketState
{
    public static $name = 'waiting_customer';

    public function label(): string
    {
        return 'Waiting on customer';
    }

    public function color(): string
    {
        return 'gray';
    }
}
