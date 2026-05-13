<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DepartmentResource\Pages;

use App\Filament\App\Resources\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateDepartment extends CreateRecord
{
    use Translatable;

    protected static string $resource = DepartmentResource::class;
}
