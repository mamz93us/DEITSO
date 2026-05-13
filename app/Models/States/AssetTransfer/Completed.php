<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

class Completed extends AssetTransferState
{
    public static $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }

    public function color(): string
    {
        return 'success';
    }
}
