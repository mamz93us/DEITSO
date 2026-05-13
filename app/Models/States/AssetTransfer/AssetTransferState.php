<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Base state for AssetTransfer.
 *
 * Allowed transitions (PROJECT.md Section 7):
 *   Draft → Pending, Cancelled
 *   Pending → Approved, Rejected, Cancelled
 *   Approved → Completed, Cancelled
 *   Completed, Rejected, Cancelled are terminal
 */
abstract class AssetTransferState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Pending::class)
            ->allowTransition(Draft::class, Cancelled::class)
            ->allowTransition(Pending::class, Approved::class)
            ->allowTransition(Pending::class, Rejected::class)
            ->allowTransition(Pending::class, Cancelled::class)
            ->allowTransition(Approved::class, Completed::class)
            ->allowTransition(Approved::class, Cancelled::class);
    }

    abstract public function label(): string;

    abstract public function color(): string;
}
