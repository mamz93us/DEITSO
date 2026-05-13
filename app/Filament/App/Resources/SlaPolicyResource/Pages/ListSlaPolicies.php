<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SlaPolicyResource\Pages;

use App\Filament\App\Resources\SlaPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSlaPolicies extends ListRecords
{
    protected static string $resource = SlaPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
