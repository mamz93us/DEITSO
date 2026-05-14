<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetScrapResource\Pages;

use App\Filament\App\Resources\AssetScrapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetScrap extends EditRecord
{
    protected static string $resource = AssetScrapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
