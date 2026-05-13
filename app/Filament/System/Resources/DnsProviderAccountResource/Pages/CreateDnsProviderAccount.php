<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\DnsProviderAccountResource\Pages;

use App\Filament\System\Resources\DnsProviderAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDnsProviderAccount extends CreateRecord
{
    protected static string $resource = DnsProviderAccountResource::class;
}
