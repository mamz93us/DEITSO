<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrProcessResource\Pages;

use App\Filament\App\Resources\HrProcessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrProcess extends EditRecord
{
    protected static string $resource = HrProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
