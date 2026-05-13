<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

class Pending extends AssetTransferState
{
    public static $name = 'pending';

    public function label(): string
    {
        return 'Pending approval';
    }

    public function color(): string
    {
        return 'warning';
    }
}
