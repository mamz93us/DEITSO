<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyTicketResource\Pages;

use App\Actions\Tickets\CreateTicket;
use App\Filament\Portal\Resources\MyTicketResource;
use App\Models\Ticket;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateMyTicket extends CreateRecord
{
    protected static string $resource = MyTicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $orgId = app('current.organization')?->id;
        if (! $orgId) {
            throw new RuntimeException('No active organization in context.');
        }

        // Stamp the authenticated user as the requester (don't trust form input).
        $data['requester_user_id'] = auth()->id();
        $data['source'] = Ticket::SOURCE_PORTAL;

        return app(CreateTicket::class)($orgId, $data);
    }
}
