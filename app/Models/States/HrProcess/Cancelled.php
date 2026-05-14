<?php

declare(strict_types=1);

namespace App\Models\States\HrProcess;

class Cancelled extends HrProcessState
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
