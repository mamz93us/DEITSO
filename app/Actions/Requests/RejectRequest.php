<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminRejected;
use App\Models\States\EmployeeRequest\ManagerApproved;
use App\Models\States\EmployeeRequest\ManagerRejected;
use App\Models\States\EmployeeRequest\Submitted;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Rejects a request. The rejecter's *role* determines which rejection state
 * is recorded (ManagerRejected vs AdminRejected) so audit + filters stay clean.
 *
 * Both rejected states are terminal.
 */
class RejectRequest
{
    public function __invoke(
        EmployeeRequest $request,
        User $rejecter,
        string $rejecterRole, // 'manager' | 'admin'
        ?string $notes = null,
    ): EmployeeRequest {
        if (! in_array($rejecterRole, ['manager', 'admin'], true)) {
            throw new RuntimeException("rejecterRole must be 'manager' or 'admin', got '{$rejecterRole}'.");
        }

        return DB::transaction(function () use ($request, $rejecter, $rejecterRole, $notes) {
            $targetState = $rejecterRole === 'manager' ? ManagerRejected::class : AdminRejected::class;

            // Defensive: only allow ManagerRejected from Submitted (not from
            // ManagerApproved — at that point only Admin can reject).
            if ($rejecterRole === 'manager' && ! $request->state instanceof Submitted) {
                throw new RuntimeException('Manager can only reject Submitted requests.');
            }
            if ($rejecterRole === 'admin' && ! ($request->state instanceof Submitted || $request->state instanceof ManagerApproved)) {
                throw new RuntimeException('Admin can only reject Submitted or ManagerApproved requests.');
            }

            $request->state->transitionTo($targetState);

            $update = $rejecterRole === 'manager'
                ? ['manager_approval_user_id' => $rejecter->id, 'manager_approval_at' => now(), 'manager_approval_notes' => $notes]
                : ['admin_approval_user_id' => $rejecter->id, 'admin_approval_at' => now(), 'admin_approval_notes' => $notes];

            $request->update($update);

            return $request->fresh();
        });
    }
}
