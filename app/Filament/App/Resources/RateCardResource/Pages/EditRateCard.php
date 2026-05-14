<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\RateCardResource\Pages;

use App\Filament\App\Resources\RateCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRateCard extends EditRecord
{
    protected static string $resource = RateCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
