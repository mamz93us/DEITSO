<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmployeeRequestResource\Pages;

use App\Filament\App\Resources\EmployeeRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeRequest extends CreateRecord
{
    protected static string $resource = EmployeeRequestResource::class;
}
