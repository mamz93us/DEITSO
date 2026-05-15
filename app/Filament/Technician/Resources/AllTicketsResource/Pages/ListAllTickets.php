<?php

declare(strict_types=1);

namespace App\Filament\Technician\Resources\AllTicketsResource\Pages;

use App\Filament\Technician\Resources\AllTicketsResource;
use Filament\Resources\Pages\ListRecords;

class ListAllTickets extends ListRecords
{
    protected static string $resource = AllTicketsResource::class;
}
