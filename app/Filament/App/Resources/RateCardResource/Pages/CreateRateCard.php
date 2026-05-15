<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\RateCardResource\Pages;

use App\Filament\App\Resources\RateCardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRateCard extends CreateRecord
{
    protected static string $resource = RateCardResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
