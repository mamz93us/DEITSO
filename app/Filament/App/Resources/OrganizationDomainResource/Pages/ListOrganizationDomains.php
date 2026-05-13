<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OrganizationDomainResource\Pages;

use App\Filament\App\Resources\OrganizationDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationDomains extends ListRecords
{
    protected static string $resource = OrganizationDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
