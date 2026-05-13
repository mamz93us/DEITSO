<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetCategoryResource\Pages;

use App\Filament\App\Resources\AssetCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditAssetCategory extends EditRecord
{
    use Translatable;

    protected static string $resource = AssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
