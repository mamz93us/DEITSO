<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Actions\Employees\CreateEmployee as CreateEmployeeAction;
use App\Filament\App\Resources\EmployeeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $orgId = app()->bound('current.organization')
            ? (is_object(app('current.organization')) ? app('current.organization')->id : app('current.organization'))
            : null;

        if (! $orgId) {
            throw new RuntimeException('No active organization in context.');
        }

        ['employee' => $employee, 'one_time_password' => $password] = app(CreateEmployeeAction::class)($orgId, $data);

        // Show the one-time password to the HR user — only once.
        Notification::make()
            ->title('Employee created')
            ->body(sprintf(
                'Login: %s | Temporary password: %s — copy now, it will not be shown again.',
                $employee->user->email,
                $password,
            ))
            ->success()
            ->persistent()
            ->send();

        return $employee;
    }
}
