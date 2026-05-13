<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

class Approved extends AssetTransferState
{
    public static $name = 'approved';

    public function label(): string
    {
        return 'Approved';
    }

    public function color(): string
    {
        return 'primary';
    }
}
