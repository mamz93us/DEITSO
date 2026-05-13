<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class ManagerApproved extends EmployeeRequestState
{
    public static $name = 'manager_approved';

    public function label(): string
    {
        return 'Manager approved';
    }

    public function color(): string
    {
        return 'warning';
    }
}
