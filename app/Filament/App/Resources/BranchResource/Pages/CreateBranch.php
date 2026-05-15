<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BranchResource\Pages;

use App\Filament\App\Resources\BranchResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateBranch extends CreateRecord
{
    use Translatable;

    protected static string $resource = BranchResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
