<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmailDomainResource\Pages;

use App\Filament\App\Resources\EmailDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailDomain extends EditRecord
{
    protected static string $resource = EmailDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
