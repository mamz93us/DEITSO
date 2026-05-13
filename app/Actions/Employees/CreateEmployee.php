<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\User;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates a User + Employee 1:1 pair in a single transaction.
 *
 * Returns the created Employee plus a one-time-display password the UI surfaces
 * once for the HR user to copy. The user is then expected to set their own
 * password via the standard reset flow (or — in a later sprint — via the
 * invitation email link).
 */
class CreateEmployee
{
    public function __construct(
        private OrganizationScopedCodeGenerator $codes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{employee: Employee, one_time_password: string}
     */
    public function __invoke(string $organizationId, array $data): array
    {
        $oneTimePassword = $data['password'] ?? Str::password(16, symbols: false);

        return DB::transaction(function () use ($organizationId, $data, $oneTimePassword) {
            $user = User::create([
                'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                'email' => $data['email'],
                'password' => Hash::make($oneTimePassword),
                'locale' => $data['locale'] ?? 'en',
                'timezone' => $data['timezone'] ?? 'Africa/Cairo',
                'is_system_admin' => false,
                'email_verified_at' => now(),
            ]);

            $code = $this->codes->next(
                Employee::class,
                $organizationId,
                prefix: 'EMP',
                padding: 4,
            );

            $employee = Employee::create([
                'organization_id' => $organizationId,
                'branch_id' => $data['branch_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'user_id' => $user->id,
                'code' => $code,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'position' => $data['position'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'personal_email' => $data['personal_email'] ?? null,
                'manager_employee_id' => $data['manager_employee_id'] ?? null,
                'hire_date' => $data['hire_date'] ?? now()->toDateString(),
                'status' => Employee::STATUS_ACTIVE,
                'custom_fields' => $data['custom_fields'] ?? null,
            ]);

            // Attach default org-membership + End User role.
            $employee->organization->users()->syncWithoutDetaching([
                $user->id => [
                    'default_role' => 'End User',
                    'joined_at' => now(),
                    'is_default' => true,
                ],
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
            $user->assignRole('End User');

            return [
                'employee' => $employee->fresh(['user', 'department', 'branch']),
                'one_time_password' => $oneTimePassword,
            ];
        });
    }
}
