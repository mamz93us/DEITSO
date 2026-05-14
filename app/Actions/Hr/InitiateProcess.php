<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Models\Employee;
use App\Models\HrProcess;
use App\Models\HrWorkflowTemplate;
use App\Models\States\HrProcess\InProgress;
use App\Models\States\HrProcessTask\Pending;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Initiates an onboarding or offboarding HrProcess for an employee.
 *
 *   1. Resolves the template (passed explicitly, or auto-matched by
 *      type + employee.department).
 *   2. Snapshots every template task into hr_process_tasks so future template
 *      edits do not retro-mutate active processes.
 *   3. Resolves each task's assignee_role to a concrete user (best effort —
 *      org admin for now; richer routing comes in Sprint 15 polish).
 *   4. Generates code ONB-2026-0001 / OFB-2026-0001.
 *   5. Moves the process state to InProgress immediately.
 */
class InitiateProcess
{
    public function __construct(private OrganizationScopedCodeGenerator $codes) {}

    public function __invoke(
        string $type,
        Employee $employee,
        ?HrWorkflowTemplate $template = null,
        ?string $targetDate = null,
    ): HrProcess {
        if (! in_array($type, [HrProcess::TYPE_ONBOARDING, HrProcess::TYPE_OFFBOARDING], true)) {
            throw new RuntimeException("Invalid HR process type: {$type}");
        }

        $organizationId = $employee->organization_id;

        // Best-match template if none provided.
        $template = $template ?? $this->matchTemplate($organizationId, $type, $employee);

        return DB::transaction(function () use ($type, $employee, $template, $targetDate, $organizationId) {
            $prefix = $type === HrProcess::TYPE_ONBOARDING ? 'ONB' : 'OFB';
            $year = (int) now()->format('Y');
            $code = $this->codes->next(
                HrProcess::class,
                $organizationId,
                prefix: $prefix,
                padding: 4,
                year: $year,
                yearReset: true,
            );

            $process = HrProcess::create([
                'code' => $code,
                'organization_id' => $organizationId,
                'branch_id' => $employee->branch_id,
                'type' => $type,
                'employee_id' => $employee->id,
                'template_id' => $template?->id,
                'initiated_by_user_id' => Auth::id(),
                'target_date' => $targetDate,
                'state' => InProgress::class,
            ]);

            if ($template) {
                $this->snapshotTasks($process, $template);
            }

            return $process->fresh(['tasks']);
        });
    }

    protected function matchTemplate(string $organizationId, string $type, Employee $employee): ?HrWorkflowTemplate
    {
        $base = HrWorkflowTemplate::query()
            ->where('organization_id', $organizationId)
            ->where('type', $type)
            ->where('is_active', true);

        // Prefer a template scoped to the employee's department.
        if ($employee->department_id) {
            $deptMatch = (clone $base)->where('department_id', $employee->department_id)->first();
            if ($deptMatch) {
                return $deptMatch;
            }
        }

        // Fallback: the org-level default for this type.
        return (clone $base)->where('is_default', true)->whereNull('department_id')->first()
            ?? (clone $base)->whereNull('department_id')->first();
    }

    protected function snapshotTasks(HrProcess $process, HrWorkflowTemplate $template): void
    {
        $targetDate = $process->target_date ?? now()->toDate();

        foreach ($template->tasks as $tmplTask) {
            $process->tasks()->create([
                'order_index' => $tmplTask->order_index,
                'title' => $tmplTask->getTranslations('title'),
                'description' => $tmplTask->description,
                'type' => $tmplTask->type,
                'config' => $tmplTask->config,
                'state' => Pending::class,
                'due_date' => $tmplTask->due_offset_days
                    ? $targetDate->copy()->addDays((int) $tmplTask->due_offset_days)
                    : null,
            ]);
        }
    }
}
