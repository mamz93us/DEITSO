<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\DnsProviderAccountResource\Pages;

use App\Filament\System\Resources\DnsProviderAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDnsProviderAccount extends EditRecord
{
    protected static string $resource = DnsProviderAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
