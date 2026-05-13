<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyRequestResource\Pages;

use App\Filament\Portal\Resources\MyRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListMyRequests extends ListRecords
{
    protected static string $resource = MyRequestResource::class;
}
