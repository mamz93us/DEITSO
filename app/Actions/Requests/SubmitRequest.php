<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminApproved;
use App\Models\States\EmployeeRequest\Submitted;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Persists a new EmployeeRequest and walks it into either Submitted (normal
 * two-step approval) or AdminApproved (auto-skip the manager step when the
 * requester has no manager set, per PROJECT.md Section 19).
 *
 * Auto-generates the REQ-YYYY-0001 code on first save.
 *
 * @param  array<string, mixed>  $data
 */
class SubmitRequest
{
    public function __construct(private OrganizationScopedCodeGenerator $codes) {}

    public function __invoke(string $organizationId, array $data): EmployeeRequest
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $year = (int) now()->format('Y');

            $code = $this->codes->next(
                EmployeeRequest::class,
                $organizationId,
                prefix: 'REQ',
                padding: 4,
                year: $year,
                yearReset: true,
            );

            $request = EmployeeRequest::create(array_merge($data, [
                'organization_id' => $organizationId,
                'code' => $code,
                'state' => Submitted::class,
                'currency' => $data['currency'] ?? config('app.default_currency', 'EGP'),
                'urgency' => $data['urgency'] ?? EmployeeRequest::URGENCY_NORMAL,
            ]));

            // Auto-skip the manager approval step if the requester has no manager.
            // The state machine permits Submitted → AdminApproved (single-step shortcut).
            $requester = Employee::withoutGlobalScopes()->find($data['requester_employee_id'] ?? null);
            if ($requester && ! $requester->manager_employee_id) {
                $request->state->transitionTo(AdminApproved::class);
                $request->update([
                    'manager_approval_notes' => 'Auto-skipped: requester has no manager set.',
                ]);
            }

            return $request->fresh();
        });
    }
}
