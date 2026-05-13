<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class Submitted extends EmployeeRequestState
{
    public static $name = 'submitted';

    public function label(): string
    {
        return 'Submitted';
    }

    public function color(): string
    {
        return 'info';
    }
}
