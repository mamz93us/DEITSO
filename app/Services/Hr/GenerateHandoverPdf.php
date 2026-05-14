<?php

declare(strict_types=1);

namespace App\Services\Hr;

use App\Models\HrProcess;
use App\Models\HrWorkflowTemplateTask;
use App\Models\States\HrProcessTask\Completed as TaskCompleted;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Generates the offboarding handover PDF — lists every collect_asset task's
 * result + condition, the employee, the date, and a signature block.
 */
class GenerateHandoverPdf
{
    public function __invoke(HrProcess $process): DomPdf
    {
        $process->loadMissing(['employee.branch', 'employee.department', 'tasks']);

        $collected = $process->tasks
            ->where('type', HrWorkflowTemplateTask::TYPE_COLLECT_ASSET)
            ->where('state', TaskCompleted::$name);

        return Pdf::loadView('pdf.offboarding-handover', [
            'process' => $process,
            'employee' => $process->employee,
            'collected' => $collected,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');
    }
}
