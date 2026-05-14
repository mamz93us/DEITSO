<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

class Failed extends HrProcessTaskState
{
    public static $name = 'failed';

    public function label(): string
    {
        return 'Failed';
    }

    public function color(): string
    {
        return 'danger';
    }
}
