<?php

declare(strict_types=1);

namespace App\Models\States\Visit;

class Completed extends VisitState
{
    public static $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }

    public function color(): string
    {
        return 'success';
    }
}
