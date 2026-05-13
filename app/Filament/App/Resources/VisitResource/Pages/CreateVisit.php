<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\VisitResource\Pages;

use App\Actions\Visits\ScheduleVisit;
use App\Filament\App\Resources\VisitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $orgId = app('current.organization')?->id;
        if (! $orgId) {
            throw new RuntimeException('No active organization in context.');
        }

        return app(ScheduleVisit::class)($orgId, $data);
    }
}
