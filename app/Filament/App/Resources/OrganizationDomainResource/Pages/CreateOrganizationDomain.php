<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OrganizationDomainResource\Pages;

use App\Filament\App\Resources\OrganizationDomainResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationDomain extends CreateRecord
{
    protected static string $resource = OrganizationDomainResource::class;
}
