<?php

declare(strict_types=1);

namespace App\Filament\Technician\Resources;

use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\TransitionTicketState;
use App\Filament\Technician\Resources\AllTicketsResource\Pages;
use App\Models\States\Ticket\Assigned;
use App\Models\States\Ticket\Cancelled;
use App\Models\States\Ticket\Closed;
use App\Models\States\Ticket\InProgress;
use App\Models\States\Ticket\NewState;
use App\Models\States\Ticket\Resolved;
use App\Models\States\Ticket\WaitingCustomer;
use App\Models\Ticket;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cross-org ticket queue for technicians. Reuses the standard Eloquent query —
 * OrganizationScope skips its filter for technicians, so this returns tickets
 * from every organization.
 */
class AllTicketsResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Work queue';

    protected static ?string $navigationLabel = 'All tickets';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('organization.slug')->label('Org')->badge()->color('info'),
                TextColumn::make('subject')->limit(50)->searchable(),
                BadgeColumn::make('priority')
                    ->colors([
                        'gray' => Ticket::PRIORITY_LOW,
                        'primary' => Ticket::PRIORITY_NORMAL,
                        'warning' => Ticket::PRIORITY_HIGH,
                        'danger' => fn ($state) => in_array($state, [Ticket::PRIORITY_URGENT, Ticket::PRIORITY_CRITICAL], true),
                    ]),
                BadgeColumn::make('state')
                    ->getStateUsing(fn (Ticket $t) => $t->state?->label() ?? '—'),
                TextColumn::make('requester.name')->label('Requester')->placeholder('—'),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('—'),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('priority')->options([
                    Ticket::PRIORITY_LOW => 'Low',
                    Ticket::PRIORITY_NORMAL => 'Normal',
                    Ticket::PRIORITY_HIGH => 'High',
                    Ticket::PRIORITY_URGENT => 'Urgent',
                    Ticket::PRIORITY_CRITICAL => 'Critical',
                ]),
                SelectFilter::make('mine')
                    ->label('Assigned to me')
                    ->query(fn (Builder $q) => $q->where('assigned_user_id', auth()->id())),
            ])
            ->actions([
                Action::make('claim')
                    ->icon('heroicon-o-hand-raised')
                    ->color('primary')
                    ->visible(fn (Ticket $t) => $t->assigned_user_id === null)
                    ->requiresConfirmation()
                    ->action(function (Ticket $t): void {
                        app(AssignTicket::class)($t, User::findOrFail(auth()->id()));
                    }),
                Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (Ticket $t) => $t->state instanceof Assigned || $t->state instanceof WaitingCustomer)
                    ->action(fn (Ticket $t) => app(TransitionTicketState::class)($t, InProgress::class)),
                Action::make('resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Ticket $t) => ! ($t->state instanceof Resolved || $t->state instanceof Closed || $t->state instanceof Cancelled || $t->state instanceof NewState))
                    ->action(fn (Ticket $t) => app(TransitionTicketState::class)($t, Resolved::class)),
                Action::make('close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->visible(fn (Ticket $t) => $t->state instanceof Resolved)
                    ->requiresConfirmation()
                    ->action(fn (Ticket $t) => app(TransitionTicketState::class)($t, Closed::class)),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllTickets::route('/'),
        ];
    }
}
