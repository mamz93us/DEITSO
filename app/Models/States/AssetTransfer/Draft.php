<?php

declare(strict_types=1);

namespace App\Models\States\AssetTransfer;

class Draft extends AssetTransferState
{
    public static $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }

    public function color(): string
    {
        return 'gray';
    }
}
