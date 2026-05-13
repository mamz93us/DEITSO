<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class Cancelled extends EmployeeRequestState
{
    public static $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }

    public function color(): string
    {
        return 'gray';
    }
}
