<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

class Rejected extends AssetTransferState
{
    public static $name = 'rejected';

    public function label(): string
    {
        return 'Rejected';
    }

    public function color(): string
    {
        return 'danger';
    }
}
