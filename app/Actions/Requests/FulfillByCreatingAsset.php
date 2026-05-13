<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Actions\Assets\CreateAsset;
use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminApproved;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The "buy new" fulfillment path: procurement creates a brand-new Asset based
 * on the request's category + model + estimated cost, then immediately calls
 * FulfillFromStock to assign it. Useful when stock is empty and the org buys
 * the device specifically for this request.
 *
 * @param  array<string, mixed>  $assetData  Extra Asset fields (serial_number, branch_id, supplier_id, …)
 */
class FulfillByCreatingAsset
{
    public function __construct(
        private CreateAsset $createAsset,
        private FulfillFromStock $fulfillFromStock,
    ) {}

    public function __invoke(EmployeeRequest $request, array $assetData = []): EmployeeRequest
    {
        if (! $request->state instanceof AdminApproved) {
            throw new RuntimeException('Only admin-approved requests can be fulfilled.');
        }

        if (! $request->category_id) {
            throw new RuntimeException('Request must have a category to create a new asset.');
        }

        return DB::transaction(function () use ($request, $assetData) {
            // Carry request defaults forward where not overridden.
            $data = array_merge([
                'category_id' => $request->category_id,
                'branch_id' => $request->branch_id ?? $request->requester?->branch_id,
                'asset_model_id' => $request->asset_model_id,
                'name' => $request->title,
                'purchase_cost_minor' => $request->estimated_cost_minor,
                'currency' => $request->currency,
            ], $assetData);

            $asset = ($this->createAsset)($request->organization_id, $data);

            return ($this->fulfillFromStock)($request, $asset);
        });
    }
}
