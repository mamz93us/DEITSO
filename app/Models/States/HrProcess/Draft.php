<?php

declare(strict_types=1);

namespace App\Models\States\HrProcess;

class Draft extends HrProcessState
{
    public static $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }

    public function color(): string
    {
        return 'gray';
    }
}
