<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmployeeRequestResource\Pages;

use App\Filament\App\Resources\EmployeeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeRequest extends EditRecord
{
    protected static string $resource = EmployeeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
