<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetScrapResource\Pages;

use App\Filament\App\Resources\AssetScrapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetScraps extends ListRecords
{
    protected static string $resource = AssetScrapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
