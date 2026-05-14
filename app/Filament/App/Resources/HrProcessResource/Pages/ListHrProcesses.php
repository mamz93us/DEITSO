<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrProcessResource\Pages;

use App\Filament\App\Resources\HrProcessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrProcesses extends ListRecords
{
    protected static string $resource = HrProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
