<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

class Completed extends HrProcessTaskState
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
