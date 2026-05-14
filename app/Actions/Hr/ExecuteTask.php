<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\AssignLicenseSeat;
use App\Actions\Assets\RevokeAssetFromEmployee;
use App\Actions\Employees\TerminateEmployee;
use App\Models\Asset;
use App\Models\HrProcessTask;
use App\Models\HrWorkflowTemplateTask;
use App\Models\States\HrProcessTask\Completed;
use App\Models\States\HrProcessTask\Failed;
use App\Models\States\HrProcessTask\InProgress;
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
        if (! $assetId) {
            throw new RuntimeException('assign_asset task requires asset_id in runtime data.');
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
