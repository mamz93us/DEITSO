<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

class AdminRejected extends EmployeeRequestState
{
    public static $name = 'admin_rejected';

    public function label(): string
    {
        return 'Admin rejected';
    }

    public function color(): string
    {
        return 'danger';
    }
}
