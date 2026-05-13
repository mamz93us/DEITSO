<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class InProcurement extends EmployeeRequestState
{
    public static $name = 'in_procurement';

    public function label(): string
    {
        return 'In procurement';
    }

    public function color(): string
    {
        return 'info';
    }
}
