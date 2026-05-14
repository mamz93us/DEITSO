<?php

declare(strict_types=1);

namespace App\Models\States\HrProcessTask;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * 6-state machine for HrProcessTask:
 *   Pending → InProgress, Blocked, Skipped
 *   InProgress → Completed, Failed, Blocked
 *   Blocked → InProgress, Pending (auto-resume), Skipped
 *   Completed | Failed | Skipped — terminal
 */
abstract class HrProcessTaskState extends State
{
    public static function config(): StateConfig
    {
        $cfg = parent::config()->default(Pending::class);

        $cfg->allowTransition(Pending::class, InProgress::class);
        $cfg->allowTransition(Pending::class, Blocked::class);
        $cfg->allowTransition(Pending::class, Skipped::class);
        $cfg->allowTransition(Pending::class, Completed::class); // direct-complete for manual tasks

        $cfg->allowTransition(InProgress::class, Completed::class);
        $cfg->allowTransition(InProgress::class, Failed::class);
        $cfg->allowTransition(InProgress::class, Blocked::class);

        $cfg->allowTransition(Blocked::class, InProgress::class);
        $cfg->allowTransition(Blocked::class, Pending::class);
        $cfg->allowTransition(Blocked::class, Skipped::class);
        $cfg->allowTransition(Blocked::class, Completed::class);

        return $cfg;
    }

    abstract public function label(): string;

    abstract public function color(): string;
}
