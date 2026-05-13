<?php

declare(strict_types=1);

namespace App\Models\States\Visit;

class Cancelled extends VisitState
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
