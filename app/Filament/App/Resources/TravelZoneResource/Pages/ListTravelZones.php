<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TravelZoneResource\Pages;

use App\Filament\App\Resources\TravelZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTravelZones extends ListRecords
{
    protected static string $resource = TravelZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
