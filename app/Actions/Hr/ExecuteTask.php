<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\AssignLicenseSeat;
use App\Actions\Assets\RevokeAssetFromEmployee;
use App\Actions\Employees\TerminateEmployee;
use App\Models\Asset;
use App\Models\EmployeeRequest;
use App\Models\HrProcessTask;
use App\Models\HrWorkflowTemplateTask;
use App\Models\States\EmployeeRequest\Submitted;
use App\Models\States\HrProcessTask\Blocked;
use App\Models\States\HrProcessTask\Completed;
use App\Models\States\HrProcessTask\Failed;
use App\Models\States\HrProcessTask\InProgress;
use App\Services\Codes\OrganizationScopedCodeGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Executes a single HrProcessTask, dispatching to a type-specific handler.
 *
 * Each task type knows how to call its module (ITAM, Employee, etc.) and what
 * to record in the `result` JSON. Failure marks the task Failed; success
 * marks it Completed and records the result. Manual tasks just flip to
 * Completed when the assignee ticks the checkbox.
 *
 * The caller passes type-specific runtime data in $runtimeData (e.g. which
 * asset to assign, which email local-part to use).
 *
 * @param  array<string, mixed>  $runtimeData
 */
class ExecuteTask
{
    public function __invoke(HrProcessTask $task, array $runtimeData = []): HrProcessTask
    {
        return DB::transaction(function () use ($task, $runtimeData) {
            $task->state->transitionTo(InProgress::class);

            try {
                $result = match ($task->type) {
                    HrWorkflowTemplateTask::TYPE_MANUAL,
                    HrWorkflowTemplateTask::TYPE_CUSTOM_ACTION => $this->executeManual($task, $runtimeData),

                    HrWorkflowTemplateTask::TYPE_ASSIGN_ASSET,
                    HrWorkflowTemplateTask::TYPE_ASSIGN_ACCESSORY => $this->executeAssignAsset($task, $runtimeData),

                    HrWorkflowTemplateTask::TYPE_ASSIGN_LICENSE => $this->executeAssignLicense($task, $runtimeData),

                    HrWorkflowTemplateTask::TYPE_COLLECT_ASSET => $this->executeCollectAsset($task, $runtimeData),

                    HrWorkflowTemplateTask::TYPE_DISABLE_USER => $this->executeDisableUser($task, $runtimeData),

                    default => $this->executeManual($task, $runtimeData), // safe fallback
                };

                $task->update(['result' => $result]);
                $task->state->transitionTo(Completed::class);
                $task->update([
                    'completed_at' => now(),
                    'completed_by_user_id' => Auth::id(),
                ]);
            } catch (HrTaskBlockedException $e) {
                // Soft failure — task is waiting on something to resolve, not Failed.
                $task->update([
                    'result' => array_merge(['blocked_reason' => $e->getMessage()], $e->context),
                    'notes' => trim(($task->notes ?? '')."\nBlocked: ".$e->getMessage()),
                ]);
                $task->state->transitionTo(Blocked::class);
                // Don't re-throw — Blocked is a normal outcome.
            } catch (Throwable $e) {
                $task->update([
                    'result' => ['error' => $e->getMessage()],
                    'notes' => trim(($task->notes ?? '')."\nFailed: ".$e->getMessage()),
                ]);
                $task->state->transitionTo(Failed::class);
                throw $e;
            }

            return $task->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeManual(HrProcessTask $task, array $data): array
    {
        return [
            'note' => $data['note'] ?? 'Marked complete manually',
            'by_user_id' => Auth::id(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeAssignAsset(HrProcessTask $task, array $data): array
    {
        $assetId = $data['asset_id'] ?? null;

        // No asset chosen? Auto-spawn an EmployeeRequest of type new_asset
        // and put this task into Blocked state — it'll resume when the request
        // fulfils. (Per PROJECT.md Section 8.4: stuck-task detection.)
        if (! $assetId) {
            $employee = $task->process->employee;
            $request = EmployeeRequest::create([
                'organization_id' => $task->process->organization_id,
                'code' => app(OrganizationScopedCodeGenerator::class)->next(
                    EmployeeRequest::class,
                    $task->process->organization_id,
                    prefix: 'REQ',
                    padding: 4,
                    year: (int) now()->format('Y'),
                    yearReset: true,
                ),
                'requester_employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'type' => EmployeeRequest::TYPE_NEW_ASSET,
                'title' => 'HR onboarding — '.($task->getTranslation('title', 'en') ?: 'new asset'),
                'description' => 'Auto-spawned by HR process '.$task->process->code,
                'urgency' => EmployeeRequest::URGENCY_HIGH,
                'state' => Submitted::class,
            ]);

            $task->update(['linked_request_id' => $request->id]);
            // Transition will happen in the caller; raise to signal Blocked.
            throw new HrTaskBlockedException(
                'No asset provided — spawned EmployeeRequest '.$request->code,
                ['linked_request_id' => $request->id, 'request_code' => $request->code],
            );
        }

        $asset = Asset::query()->withoutGlobalScopes()->findOrFail($assetId);
        $employee = $task->process->employee;

        app(AssignAssetToEmployee::class)($asset, $employee, 'HR process '.$task->process->code);

        return ['asset_id' => $asset->id, 'asset_code' => $asset->code];
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeAssignLicense(HrProcessTask $task, array $data): array
    {
        $licenseId = $data['license_id'] ?? $data['asset_id'] ?? null;
        if (! $licenseId) {
            throw new RuntimeException('assign_license task requires license_id in runtime data.');
        }

        $license = Asset::query()->withoutGlobalScopes()->findOrFail($licenseId);
        $employee = $task->process->employee;

        app(AssignLicenseSeat::class)($license, $employee, 'HR process '.$task->process->code);

        return ['license_id' => $license->id, 'license_code' => $license->code];
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeCollectAsset(HrProcessTask $task, array $data): array
    {
        $assetId = $data['asset_id'] ?? null;
        if (! $assetId) {
            throw new RuntimeException('collect_asset task requires asset_id in runtime data.');
        }

        $asset = Asset::query()->withoutGlobalScopes()->findOrFail($assetId);
        app(RevokeAssetFromEmployee::class)($asset, 'Offboarding '.$task->process->code);

        return [
            'asset_id' => $asset->id,
            'asset_code' => $asset->code,
            'condition' => $data['condition'] ?? 'good',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeDisableUser(HrProcessTask $task): array
    {
        $employee = $task->process->employee;
        app(TerminateEmployee::class)($employee, null, 'Offboarding '.$task->process->code);

        return ['employee_id' => $employee->id, 'user_disabled' => true];
    }
}
