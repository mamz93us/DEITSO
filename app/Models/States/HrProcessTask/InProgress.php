<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

class InProgress extends HrProcessTaskState
{
    public static $name = 'in_progress';

    public function label(): string
    {
        return 'In progress';
    }

    public function color(): string
    {
        return 'info';
    }
}
