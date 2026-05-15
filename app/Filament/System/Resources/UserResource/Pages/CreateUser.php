<?php

declare(strict_types=1);

namespace App\Filament\System\Resources\UserResource\Pages;

use App\Filament\System\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
