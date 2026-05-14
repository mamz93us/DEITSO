<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MailServerResource\Pages;

use App\Filament\App\Resources\MailServerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailServer extends CreateRecord
{
    protected static string $resource = MailServerResource::class;
}
