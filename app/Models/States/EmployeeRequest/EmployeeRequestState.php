<?php

declare(strict_types=1);

namespace App\Models\States\EmployeeRequest;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * State machine for EmployeeRequest (PROJECT.md Section 8.3).
 *
 *   Draft       → Submitted, Cancelled
 *   Submitted   → ManagerApproved, ManagerRejected, AdminApproved (single-mode), Cancelled
 *   ManagerApproved → AdminApproved, AdminRejected, Cancelled
 *   AdminApproved   → InProcurement, Cancelled
 *   InProcurement   → Fulfilled
 *   ManagerRejected | AdminRejected | Fulfilled | Cancelled — terminal
 */
abstract class EmployeeRequestState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Submitted::class)
            ->allowTransition(Draft::class, Cancelled::class)

            ->allowTransition(Submitted::class, ManagerApproved::class)
            ->allowTransition(Submitted::class, ManagerRejected::class)
            // Single-approval-mode shortcut.
            ->allowTransition(Submitted::class, AdminApproved::class)
            ->allowTransition(Submitted::class, Cancelled::class)

            ->allowTransition(ManagerApproved::class, AdminApproved::class)
            ->allowTransition(ManagerApproved::class, AdminRejected::class)
            ->allowTransition(ManagerApproved::class, Cancelled::class)

            ->allowTransition(AdminApproved::class, InProcurement::class)
            ->allowTransition(AdminApproved::class, Cancelled::class)

            ->allowTransition(InProcurement::class, Fulfilled::class);
    }

    abstract public function label(): string;

    abstract public function color(): string;
}
