<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrWorkflowTemplateResource\Pages;

use App\Filament\App\Resources\HrWorkflowTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrWorkflowTemplate extends EditRecord
{
    protected static string $resource = HrWorkflowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
