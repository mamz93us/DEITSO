<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MailServerResource\Pages;

use App\Filament\App\Resources\MailServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailServer extends EditRecord
{
    protected static string $resource = MailServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
