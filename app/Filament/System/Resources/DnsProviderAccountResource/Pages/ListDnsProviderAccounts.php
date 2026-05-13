<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\DnsProviderAccountResource\Pages;

use App\Filament\System\Resources\DnsProviderAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDnsProviderAccounts extends ListRecords
{
    protected static string $resource = DnsProviderAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
