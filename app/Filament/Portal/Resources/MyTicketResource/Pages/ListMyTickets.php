<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyTicketResource\Pages;

use App\Filament\Portal\Resources\MyTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMyTickets extends ListRecords
{
    protected static string $resource = MyTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Raise a ticket')];
    }
}
