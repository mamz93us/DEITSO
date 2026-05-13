<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Expose the linked user's email even if the user is soft-deleted
        // (i.e. this employee has been terminated). withTrashed() bypasses
        // the SoftDeletes scope so the email is still visible.
        $user = $this->record?->user()->withTrashed()->first();
        if ($user) {
            $data['email'] = $user->email;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
