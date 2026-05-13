<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyAssetResource\Pages;

use App\Filament\Portal\Resources\MyAssetResource;
use Filament\Resources\Pages\ListRecords;

class ListMyAssets extends ListRecords
{
    protected static string $resource = MyAssetResource::class;
}
