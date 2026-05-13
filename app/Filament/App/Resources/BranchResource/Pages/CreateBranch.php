<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BranchResource\Pages;

use App\Filament\App\Resources\BranchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;
}
