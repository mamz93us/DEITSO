<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\OrganizationResource\Pages;

use App\Filament\System\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\Translatable;

class ListOrganizations extends ListRecords
{
    use Translatable;

    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
