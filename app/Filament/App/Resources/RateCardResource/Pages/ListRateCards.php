<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\RateCardResource\Pages;

use App\Filament\App\Resources\RateCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRateCards extends ListRecords
{
    protected static string $resource = RateCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
