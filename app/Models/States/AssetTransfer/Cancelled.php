<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

class Cancelled extends AssetTransferState
{
    public static $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }

    public function color(): string
    {
        return 'gray';
    }
}
