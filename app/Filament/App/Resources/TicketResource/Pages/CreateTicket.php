<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TicketResource\Pages;

use App\Actions\Tickets\CreateTicket as CreateTicketAction;
use App\Filament\App\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $orgId = app('current.organization')?->id;
        if (! $orgId) {
            throw new RuntimeException('No active organization in context.');
        }

        return app(CreateTicketAction::class)($orgId, $data);
    }
}
