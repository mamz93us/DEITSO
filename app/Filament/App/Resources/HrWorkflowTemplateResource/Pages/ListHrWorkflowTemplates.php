<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrWorkflowTemplateResource\Pages;

use App\Filament\App\Resources\HrWorkflowTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrWorkflowTemplates extends ListRecords
{
    protected static string $resource = HrWorkflowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
