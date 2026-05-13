<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminApproved;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Admin step. Works in both single-approval-mode (Submitted → AdminApproved)
 * and two-step (ManagerApproved → AdminApproved). The state machine permits
 * both transitions.
 */
class ApproveByAdmin
{
    public function __invoke(EmployeeRequest $request, User $admin, ?string $notes = null): EmployeeRequest
    {
        return DB::transaction(function () use ($request, $admin, $notes) {
            $request->state->transitionTo(AdminApproved::class);
            $request->update([
                'admin_approval_user_id' => $admin->id,
                'admin_approval_at' => now(),
                'admin_approval_notes' => $notes,
            ]);

            return $request->fresh();
        });
    }
}
