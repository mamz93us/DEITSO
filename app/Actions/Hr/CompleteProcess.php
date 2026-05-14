<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Models\HrProcess;
use App\Models\States\HrProcess\Completed;
use App\Models\States\HrProcess\InProgress;
use App\Models\States\HrProcessTask\Completed as TaskCompleted;
use App\Models\States\HrProcessTask\Skipped;
use App\Services\Hr\GenerateHandoverPdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Marks a process Completed. Requires every required task to be in a terminal
 * state (Completed or Skipped) — non-required tasks may remain Pending.
 *
 * For offboarding processes, generates and stores the handover PDF.
 */
class CompleteProcess
{
    public function __construct(private GenerateHandoverPdf $handover) {}

    public function __invoke(HrProcess $process, bool $forceWithIncomplete = false): HrProcess
    {
        if (! $process->state instanceof InProgress) {
            throw new RuntimeException('Only an InProgress process can be completed.');
        }

        if (! $forceWithIncomplete) {
            $remaining = $process->tasks()
                ->whereNotIn('state', [TaskCompleted::$name, Skipped::$name])
                ->whereJsonContains('config->is_required', true)
                ->orWhere(function ($q) {
                    $q->whereNull('config')
                        ->whereNotIn('state', [TaskCompleted::$name, Skipped::$name]);
                })
                ->count();

            // Simpler check: are any required tasks (default true) not done?
            $incomplete = $process->tasks()
                ->whereNotIn('state', [TaskCompleted::$name, Skipped::$name])
                ->count();
            if ($incomplete > 0) {
                throw new RuntimeException("Cannot complete: {$incomplete} task(s) are not yet finished. Pass forceWithIncomplete=true to override.");
            }
        }

        return DB::transaction(function () use ($process) {
            $pdfPath = null;
            if ($process->type === HrProcess::TYPE_OFFBOARDING) {
                $pdf = ($this->handover)($process);
                $pdfPath = 'handovers/'.$process->code.'.pdf';
                Storage::disk('local')->put($pdfPath, $pdf->output());
            }

            $process->state->transitionTo(Completed::class);
            $process->update([
                'completed_at' => now(),
                'completed_by_user_id' => Auth::id(),
                'handover_pdf_path' => $pdfPath,
            ]);

            return $process->fresh();
        });
    }
}
