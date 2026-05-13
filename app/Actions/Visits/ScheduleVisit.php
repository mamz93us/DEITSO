<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\Models\States\Visit\Scheduled;
use App\Models\Visit;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new Visit in Scheduled state with auto-generated VST-YYYY-0001 code.
 *
 * @param  array<string, mixed>  $data
 */
class ScheduleVisit
{
    public function __construct(private OrganizationScopedCodeGenerator $codes) {}

    public function __invoke(string $organizationId, array $data): Visit
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $year = (int) now()->format('Y');
            $code = $this->codes->next(
                Visit::class,
                $organizationId,
                prefix: 'VST',
                padding: 4,
                year: $year,
                yearReset: true,
            );

            return Visit::create(array_merge($data, [
                'code' => $code,
                'organization_id' => $organizationId,
                'state' => Scheduled::class,
                'currency' => $data['currency'] ?? config('app.default_currency', 'EGP'),
            ]));
        });
    }
}
