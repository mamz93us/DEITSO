<?php

declare(strict_types=1);

namespace App\Actions\Requests;

use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\Submitted;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Persists a new EmployeeRequest (or transitions an existing draft) into the
 * Submitted state. Auto-generates the per-org-per-year code on first save.
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
                'state' => Submitted::class, // start submitted (drafts only via UI)
                'currency' => $data['currency'] ?? config('app.default_currency', 'EGP'),
                'urgency' => $data['urgency'] ?? EmployeeRequest::URGENCY_NORMAL,
            ]));

            return $request->fresh();
        });
    }
}
