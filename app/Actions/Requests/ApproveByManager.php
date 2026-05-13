<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\ManagerApproved;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Manager step of the two-step approval flow. Transitions Submitted →
 * ManagerApproved and records who approved + when + optional notes.
 *
 * Idempotent: re-calling on an already-ManagerApproved request is a no-op.
 */
class ApproveByManager
{
    public function __invoke(EmployeeRequest $request, User $manager, ?string $notes = null): EmployeeRequest
    {
        return DB::transaction(function () use ($request, $manager, $notes) {
            $request->state->transitionTo(ManagerApproved::class);
            $request->update([
                'manager_approval_user_id' => $manager->id,
                'manager_approval_at' => now(),
                'manager_approval_notes' => $notes,
            ]);

            return $request->fresh();
        });
    }
}
