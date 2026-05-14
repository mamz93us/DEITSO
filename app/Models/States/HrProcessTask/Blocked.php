<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

class Blocked extends HrProcessTaskState
{
    public static $name = 'blocked';

    public function label(): string
    {
        return 'Blocked';
    }

    public function color(): string
    {
        return 'warning';
    }
}
