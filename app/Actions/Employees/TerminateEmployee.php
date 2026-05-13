<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sets an employee to terminated and soft-deletes their User account.
 *
 * Historical asset assignments / tickets / requests retain the employee_id
 * reference for audit purposes — only the *active* state is cleared.
 *
 * Note: this is the "manual" termination path. The full offboarding HR
 * workflow (Sprint 14) wraps this action inside a multi-task process.
 */
class TerminateEmployee
{
    public function __invoke(Employee $employee, ?Carbon $terminationDate = null, ?string $reason = null): Employee
    {
        return DB::transaction(function () use ($employee, $terminationDate, $reason) {
            $employee->update([
                'status' => Employee::STATUS_TERMINATED,
                'termination_date' => ($terminationDate ?? now())->toDateString(),
                'custom_fields' => array_merge(
                    (array) ($employee->custom_fields ?? []),
                    array_filter(['termination_reason' => $reason]),
                ),
            ]);

            // Rename the email before soft-delete so the original address is
            // freed up for re-hire. The DB unique constraint on users.email
            // would otherwise block a new employee with the same address.
            if ($user = $employee->user) {
                $original = $user->email;
                $user->forceFill([
                    'email' => sprintf('terminated.%s.%s', $user->id, $original),
                ])->save();

                $user->delete(); // soft delete
            }

            return $employee->fresh(['user']);
        });
    }
}
