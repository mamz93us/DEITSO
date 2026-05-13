<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class Fulfilled extends EmployeeRequestState
{
    public static $name = 'fulfilled';

    public function label(): string
    {
        return 'Fulfilled';
    }

    public function color(): string
    {
        return 'success';
    }
}
