<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class ManagerRejected extends EmployeeRequestState
{
    public static $name = 'manager_rejected';

    public function label(): string
    {
        return 'Manager rejected';
    }

    public function color(): string
    {
        return 'danger';
    }
}
