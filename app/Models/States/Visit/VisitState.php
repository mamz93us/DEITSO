<?php

declare(strict_types=1);

namespace App\Models\States\Visit;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class VisitState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Scheduled::class)
            ->allowTransition(Scheduled::class, InProgress::class)
            ->allowTransition(Scheduled::class, Cancelled::class)
            ->allowTransition(InProgress::class, Completed::class)
            ->allowTransition(InProgress::class, Cancelled::class);
    }

    abstract public function label(): string;

    abstract public function color(): string;
}
