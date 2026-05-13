<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\AssignLicenseSeat;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminApproved;
use App\Models\States\EmployeeRequest\Fulfilled;
use App\Models\States\EmployeeRequest\InProcurement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fulfills a request by assigning an existing in-stock asset to the requester.
 * Also stamps `Asset.source_request_id` so we keep traceability from purchase
 * back to the original request.
 *
 * Transitions AdminApproved → InProcurement → Fulfilled (the InProcurement step
 * is logged for the audit trail even when no actual purchase happens).
 */
class FulfillFromStock
{
    public function __construct(
        private AssignAssetToEmployee $assign,
        private AssignLicenseSeat $assignSeat,
    ) {}

    public function __invoke(EmployeeRequest $request, Asset $asset): EmployeeRequest
    {
        if (! $request->state instanceof AdminApproved) {
            throw new RuntimeException('Only admin-approved requests can be fulfilled.');
        }

        if ($asset->organization_id !== $request->organization_id) {
            throw new RuntimeException('Cross-tenant fulfillment is not allowed.');
        }

        return DB::transaction(function () use ($request, $asset) {
            // Walk the state machine for the audit trail.
            $request->state->transitionTo(InProcurement::class);

            $employee = $request->requester;
            if (! $employee) {
                throw new RuntimeException('Request has no requester employee.');
            }

            // Licenses are seat-based; non-licenses are single-holder.
            if ($asset->tracking_mode === AssetCategory::TRACKING_LICENSE) {
                ($this->assignSeat)($asset, $employee, 'request '.$request->code);
            } else {
                ($this->assign)($asset, $employee, 'request '.$request->code);
            }

            // Stamp traceability back to the originating request.
            $asset->update(['source_request_id' => $request->id]);

            $request->state->transitionTo(Fulfilled::class);
            $request->update([
                'fulfilled_at' => now(),
                'fulfillment_asset_id' => $asset->id,
            ]);

            return $request->fresh();
        });
    }
}
