<?php

declare(strict_types=1);

namespace App\Models\States\Visit;

class Scheduled extends VisitState
{
    public static $name = 'scheduled';

    public function label(): string
    {
        return 'Scheduled';
    }

    public function color(): string
    {
        return 'info';
    }
}
