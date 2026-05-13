<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\OrganizationResource\Pages;

use App\Filament\System\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditOrganization extends EditRecord
{
    use Translatable;

    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
