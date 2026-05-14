<?php

declare(strict_types=1);

namespace App\Models\States\HrProcess;

class Completed extends HrProcessState
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
