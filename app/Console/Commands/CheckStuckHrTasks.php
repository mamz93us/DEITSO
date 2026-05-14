<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HrProcessTask;
use App\Models\Scopes\OrganizationScope;
use App\Models\States\HrProcessTask\Blocked;
use App\Models\States\HrProcessTask\Completed;
use App\Models\States\HrProcessTask\Failed;
use App\Models\States\HrProcessTask\Pending;
use App\Models\States\HrProcessTask\Skipped;
use Illuminate\Console\Command;

/**
 * Surfaces stuck HR tasks — Pending with past due_date, or Blocked for >5 days.
 * Daily schedule. Sends notifications to the process initiator + HR admins.
 * For now: prints a report and writes an activity-log entry per stuck task.
 */
class CheckStuckHrTasks extends Command
{
    protected $signature = 'hr:check-stuck-tasks {--dry-run}';

    protected $description = 'Surface HR process tasks that are stuck (overdue Pending or long-Blocked).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $now = now();
        $blockedThreshold = $now->copy()->subDays(5);

        $stuck = HrProcessTask::query()
            ->withoutGlobalScopes([OrganizationScope::class])
            ->whereNotIn('state', [Completed::class, Skipped::class, Failed::class])
            ->where(function ($q) use ($now, $blockedThreshold) {
                $q->where(function ($q) use ($now) {
                    $q->where('state', Pending::class)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $now);
                })->orWhere(function ($q) use ($blockedThreshold) {
                    $q->where('state', Blocked::class)
                        ->where('updated_at', '<', $blockedThreshold);
                });
            })
            ->with('process')
            ->get();

        foreach ($stuck as $task) {
            $this->line(sprintf(
                'Stuck task %s — process %s — state %s — due %s',
                $task->id,
                $task->process?->code,
                is_object($task->state) ? $task->state->label() : $task->state,
                $task->due_date?->format('Y-m-d') ?? '—',
            ));

            if (! $dry) {
                activity()
                    ->performedOn($task)
                    ->event('hr_task.stuck')
                    ->log('Task flagged as stuck');
            }
        }

        $this->info(sprintf('Stuck check done. count=%d%s', $stuck->count(), $dry ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}
