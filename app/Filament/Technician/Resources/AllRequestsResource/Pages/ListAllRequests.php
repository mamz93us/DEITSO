<?php

declare(strict_types=1);

namespace App\Filament\Technician\Resources\AllRequestsResource\Pages;

use App\Filament\Technician\Resources\AllRequestsResource;
use Filament\Resources\Pages\ListRecords;

class ListAllRequests extends ListRecords
{
    protected static string $resource = AllRequestsResource::class;
}
