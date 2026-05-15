<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TicketCategoryResource\Pages;

use App\Filament\App\Resources\TicketCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketCategory extends CreateRecord
{
    protected static string $resource = TicketCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
