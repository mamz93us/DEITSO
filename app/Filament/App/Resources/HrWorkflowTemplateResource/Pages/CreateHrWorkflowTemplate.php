<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrWorkflowTemplateResource\Pages;

use App\Filament\App\Resources\HrWorkflowTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrWorkflowTemplate extends CreateRecord
{
    protected static string $resource = HrWorkflowTemplateResource::class;
}
