<?php

declare(strict_types=1);

namespace App\Models\States\HrProcess;

class InProgress extends HrProcessState
{
    public static $name = 'in_progress';

    public function label(): string
    {
        return 'In progress';
    }

    public function color(): string
    {
        return 'warning';
    }
}
