<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\OrganizationResource\Pages;

use App\Filament\System\Resources\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateOrganization extends CreateRecord
{
    use Translatable;

    protected static string $resource = OrganizationResource::class;
}
