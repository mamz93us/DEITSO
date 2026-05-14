<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use RuntimeException;

/**
 * Thrown by typed task handlers when a task can't proceed yet but isn't a
 * hard failure (e.g. assign_asset has no stock and we spawned a request).
 * ExecuteTask catches this and transitions the task to Blocked, recording
 * the context. The task resumes when the blocking condition clears.
 */
class HrTaskBlockedException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message, public array $context = [])
    {
        parent::__construct($message);
    }
}
