<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetCategoryResource\Pages;

use App\Filament\App\Resources\AssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateAssetCategory extends CreateRecord
{
    use Translatable;

    protected static string $resource = AssetCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
