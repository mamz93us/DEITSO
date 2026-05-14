<?php

declare(strict_types=1);

namespace App\Models\States\HrProcess;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class HrProcessState extends State
{
    public static function config(): StateConfig
    {
        $cfg = parent::config()->default(Draft::class);

        $cfg->allowTransition(Draft::class, InProgress::class);
        $cfg->allowTransition(Draft::class, Cancelled::class);

        $cfg->allowTransition(InProgress::class, Completed::class);
        $cfg->allowTransition(InProgress::class, Cancelled::class);

        return $cfg;
    }

    abstract public function label(): string;

    abstract public function color(): string;
}
