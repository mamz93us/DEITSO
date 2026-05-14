<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TicketResource\Pages;

use App\Filament\App\Resources\TicketResource;
use App\Models\States\Ticket\Assigned;
use App\Models\States\Ticket\Closed;
use App\Models\States\Ticket\InProgress;
use App\Models\States\Ticket\NewState;
use App\Models\States\Ticket\Resolved;
use App\Models\States\Ticket\WaitingCustomer;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    public function getTabs(): array
    {
        $cnt = fn (string $state) => Ticket::query()->where('state', $state)->count();

        return [
            'all' => Tab::make('All'),
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', NewState::class))
                ->badge($cnt(NewState::class)),
            'assigned' => Tab::make('Assigned')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', Assigned::class))
                ->badge($cnt(Assigned::class)),
            'in_progress' => Tab::make('In progress')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', InProgress::class))
                ->badge($cnt(InProgress::class)),
            'waiting' => Tab::make('Waiting on customer')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', WaitingCustomer::class))
                ->badge($cnt(WaitingCustomer::class)),
            'resolved' => Tab::make('Resolved')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', Resolved::class)),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', Closed::class)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New ticket'),
        ];
    }
}
