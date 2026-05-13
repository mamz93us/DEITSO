<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class AdminApproved extends EmployeeRequestState
{
    public static $name = 'admin_approved';

    public function label(): string
    {
        return 'Admin approved';
    }

    public function color(): string
    {
        return 'primary';
    }
}
