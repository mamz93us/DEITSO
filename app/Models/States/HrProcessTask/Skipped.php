<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

class Skipped extends HrProcessTaskState
{
    public static $name = 'skipped';

    public function label(): string
    {
        return 'Skipped';
    }

    public function color(): string
    {
        return 'gray';
    }
}
