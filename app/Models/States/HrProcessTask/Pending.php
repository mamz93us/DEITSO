<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

class Pending extends HrProcessTaskState
{
    public static $name = 'pending';

    public function label(): string
    {
        return 'Pending';
    }

    public function color(): string
    {
        return 'gray';
    }
}
