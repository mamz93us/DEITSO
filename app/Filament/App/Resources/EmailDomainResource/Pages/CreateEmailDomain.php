<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmailDomainResource\Pages;

use App\Filament\App\Resources\EmailDomainResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailDomain extends CreateRecord
{
    protected static string $resource = EmailDomainResource::class;
}
