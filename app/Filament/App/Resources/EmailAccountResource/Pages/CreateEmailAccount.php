<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmailAccountResource\Pages;

use App\Filament\App\Resources\EmailAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailAccount extends CreateRecord
{
    protected static string $resource = EmailAccountResource::class;
}
