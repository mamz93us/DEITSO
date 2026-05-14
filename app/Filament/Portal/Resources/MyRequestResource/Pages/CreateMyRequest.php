<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyRequestResource\Pages;

use App\Actions\Requests\SubmitRequest;
use App\Filament\Portal\Resources\MyRequestResource;
use App\Models\EmployeeRequest;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateMyRequest extends CreateRecord
{
    protected static string $resource = MyRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $employee = auth()->user()?->employee;
        if (! $employee) {
            Notification::make()
                ->title('No employee profile')
                ->body('Your user account isn\'t linked to an employee record yet. Please contact HR.')
                ->danger()
                ->send();
            throw new RuntimeException('Authenticated user has no employee profile.');
        }

        if (! app()->bound('current.organization') || ! app('current.organization')) {
            throw new RuntimeException('No active organization in context.');
        }

        $orgId = app('current.organization')->id;

        return app(SubmitRequest::class)($orgId, array_merge($data, [
            'requester_employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $code = $this->record instanceof EmployeeRequest ? $this->record->code : 'created';

        return Notification::make()
            ->title('Request submitted')
            ->body('We\'ve received your request ('.$code.'). You\'ll be notified as it progresses.')
            ->success();
    }
}
