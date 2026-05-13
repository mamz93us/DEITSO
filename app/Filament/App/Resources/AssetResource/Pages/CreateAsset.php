<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetResource\Pages;

use App\Actions\Assets\CreateAsset as CreateAssetAction;
use App\Filament\App\Resources\AssetResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $orgId = app('current.organization')?->id;
        if (! $orgId) {
            throw new RuntimeException('No active organization in context.');
        }

        return app(CreateAssetAction::class)($orgId, $data);
    }
}
