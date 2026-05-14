<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmailAccountResource\Pages;

use App\Filament\App\Resources\EmailAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailAccount extends EditRecord
{
    protected static string $resource = EmailAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
