<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\OrganizationResource\Pages;

use App\Filament\System\Resources\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;
}
