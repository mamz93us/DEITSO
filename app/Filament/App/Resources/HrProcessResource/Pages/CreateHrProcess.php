<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrProcessResource\Pages;

use App\Actions\Hr\InitiateProcess;
use App\Filament\App\Resources\HrProcessResource;
use App\Models\Employee;
use App\Models\HrWorkflowTemplate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateHrProcess extends CreateRecord
{
    protected static string $resource = HrProcessResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $template = ! empty($data['template_id']) ? HrWorkflowTemplate::find($data['template_id']) : null;

        return app(InitiateProcess::class)(
            $data['type'],
            $employee,
            $template,
            $data['target_date'] ?? null,
        );
    }
}
