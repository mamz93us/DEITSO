<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\SlaPolicy;
use App\Models\States\Ticket\NewState;
use App\Models\Ticket;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates a ticket with an auto-generated TCK-YYYY-00001 code and computes the
 * SLA deadlines based on the matching SlaPolicy (matched on priority + org).
 *
 * If no SlaPolicy matches, the ticket is created without SLA timers.
 *
 * @param  array<string, mixed>  $data
 */
class CreateTicket
{
    public function __construct(private OrganizationScopedCodeGenerator $codes) {}

    public function __invoke(string $organizationId, array $data): Ticket
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $year = (int) now()->format('Y');
            $code = $this->codes->next(
                Ticket::class,
                $organizationId,
                prefix: 'TCK',
                padding: 5,
                year: $year,
                yearReset: true,
            );

            $priority = $data['priority'] ?? Ticket::PRIORITY_NORMAL;
            $openedAt = Carbon::now();

            // Resolve the matching SLA policy (org + priority) or fall back to org default.
            $sla = SlaPolicy::query()
                ->where('organization_id', $organizationId)
                ->where('priority', $priority)
                ->first()
                ?? SlaPolicy::query()
                    ->where('organization_id', $organizationId)
                    ->where('is_default', true)
                    ->first();

            $slaResponseDueAt = $sla ? $openedAt->copy()->addMinutes($sla->response_minutes) : null;
            $slaResolutionDueAt = $sla ? $openedAt->copy()->addMinutes($sla->resolution_minutes) : null;

            return Ticket::create(array_merge($data, [
                'code' => $code,
                'organization_id' => $organizationId,
                'priority' => $priority,
                'state' => NewState::class,
                'source' => $data['source'] ?? Ticket::SOURCE_WEB,
                'sla_policy_id' => $sla?->id,
                'opened_at' => $openedAt,
                'sla_response_due_at' => $slaResponseDueAt,
                'sla_resolution_due_at' => $slaResolutionDueAt,
            ]));
        });
    }
}
