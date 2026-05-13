<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetModelResource\Pages;

use App\Filament\App\Resources\AssetModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetModel extends CreateRecord
{
    protected static string $resource = AssetModelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Stamp the active org so this model is org-scoped (not a system template).
        $data['organization_id'] = app('current.organization')?->id;

        return $data;
    }
}
