<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\Cancelled;
use App\Models\States\EmployeeRequest\Fulfilled;
use App\Models\States\EmployeeRequest\InProcurement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Requester-initiated cancel. Allowed any time before InProcurement —
 * once procurement has started buying, the requester loses the ability to
 * cancel (per blueprint Section 8.3).
 */
class CancelRequest
{
    public function __invoke(EmployeeRequest $request): EmployeeRequest
    {
        if ($request->state instanceof InProcurement || $request->state instanceof Fulfilled) {
            throw new RuntimeException('Cannot cancel a request that is already in procurement or fulfilled.');
        }

        return DB::transaction(function () use ($request) {
            $request->state->transitionTo(Cancelled::class);

            return $request->fresh();
        });
    }
}
