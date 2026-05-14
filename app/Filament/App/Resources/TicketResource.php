<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\TransitionTicketState;
use App\Filament\App\Resources\TicketResource\Pages;
use App\Filament\App\Resources\TicketResource\RelationManagers\CommentsRelationManager;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\States\Ticket\Assigned;
use App\Models\States\Ticket\Cancelled;
use App\Models\States\Ticket\Closed;
use App\Models\States\Ticket\InProgress;
use App\Models\States\Ticket\NewState;
use App\Models\States\Ticket\Resolved;
use App\Models\States\Ticket\WaitingCustomer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Tickets';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Ticket')
                ->columns(2)
                ->schema([
                    TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(4)->columnSpanFull(),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => TicketCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->searchable()
                        ->nullable(),

                    Select::make('priority')
                        ->options([
                            Ticket::PRIORITY_LOW => 'Low',
                            Ticket::PRIORITY_NORMAL => 'Normal',
                            Ticket::PRIORITY_HIGH => 'High',
                            Ticket::PRIORITY_URGENT => 'Urgent',
                            Ticket::PRIORITY_CRITICAL => 'Critical',
                        ])
                        ->default(Ticket::PRIORITY_NORMAL)
                        ->required()
                        ->native(false),

                    Select::make('source')
                        ->options([
                            Ticket::SOURCE_WEB => 'Web',
                            Ticket::SOURCE_PORTAL => 'Portal',
                            Ticket::SOURCE_EMAIL => 'Email',
                            Ticket::SOURCE_WHATSAPP => 'WhatsApp',
                            Ticket::SOURCE_PHONE => 'Phone',
                            Ticket::SOURCE_WALK_IN => 'Walk-in',
                        ])
                        ->default(Ticket::SOURCE_WEB)
                        ->required()
                        ->native(false),

                    Select::make('requester_user_id')
                        ->label('Requester')
                        ->options(fn () => User::query()
                            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', app('current.organization')?->id))
                            ->orderBy('name')->limit(500)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Select::make('assigned_user_id')
                        ->label('Assignee')
                        ->options(fn () => User::query()
                            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', app('current.organization')?->id))
                            ->orderBy('name')->limit(500)->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    Select::make('branch_id')
                        ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                        ->searchable()
                        ->nullable(),
                ]),

            Section::make('Related records')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Select::make('related_asset_id')
                        ->label('Related asset')
                        ->options(fn () => Asset::query()->orderBy('code')->limit(500)->pluck('code', 'id'))
                        ->searchable()
                        ->nullable(),

                    Select::make('related_employee_id')
                        ->label('Related employee')
                        ->options(fn () => Employee::query()->get()->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                        ->searchable()
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('subject')->limit(50)->searchable(),
                BadgeColumn::make('priority')->colors([
                    'gray' => Ticket::PRIORITY_LOW,
                    'primary' => Ticket::PRIORITY_NORMAL,
                    'warning' => Ticket::PRIORITY_HIGH,
                    'danger' => [Ticket::PRIORITY_URGENT, Ticket::PRIORITY_CRITICAL],
                ]),
                BadgeColumn::make('state')
                    ->getStateUsing(fn (Ticket $t) => $t->state?->label() ?? '—'),
                TextColumn::make('assignee.name')->label('Assignee')->placeholder('—')->toggleable(),
                TextColumn::make('requester.name')->label('Requester')->toggleable(),
                TextColumn::make('sla_response_due_at')
                    ->label('Response due')
                    ->dateTime()
                    ->color(fn (Ticket $t) => $t->is_response_breached ? 'danger' : null)
                    ->toggleable(),
                TextColumn::make('sla_resolution_due_at')
                    ->label('Resolution due')
                    ->dateTime()
                    ->color(fn (Ticket $t) => $t->is_resolution_breached ? 'danger' : null)
                    ->toggleable(),
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
                SelectFilter::make('assigned_user_id')
                    ->label('Assignee')
                    ->options(fn () => User::query()->orderBy('name')->limit(500)->pluck('name', 'id')),
                TernaryFilter::make('breached')
                    ->label('SLA breached')
                    ->queries(
                        true: fn ($q) => $q->where(fn ($q) => $q->whereColumn('sla_response_due_at', '<', 'opened_at')
                            ->orWhere(fn ($q) => $q->whereNull('first_response_at')->where('sla_response_due_at', '<', now()))
                            ->orWhere(fn ($q) => $q->whereNull('resolved_at')->where('sla_resolution_due_at', '<', now()))),
                        false: fn ($q) => $q,
                        blank: fn ($q) => $q,
                    ),
            ])
            ->actions([
                EditAction::make(),
                Action::make('assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (Ticket $t) => $t->state instanceof NewState || $t->assigned_user_id === null)
                    ->form([
                        Select::make('user_id')
                            ->options(fn () => User::query()
                                ->whereHas('organizations', fn ($q) => $q->where('organizations.id', app('current.organization')?->id))
                                ->orderBy('name')->limit(500)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Ticket $t, array $data) {
                        app(AssignTicket::class)($t, User::findOrFail($data['user_id']));
                    }),
                Action::make('start')
                    ->label('Start working')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (Ticket $t) => $t->state instanceof Assigned || $t->state instanceof WaitingCustomer)
                    ->action(function (Ticket $t) {
                        app(TransitionTicketState::class)($t, InProgress::class);
                    }),
                Action::make('wait_customer')
                    ->label('Wait on customer')
                    ->icon('heroicon-o-pause')
                    ->color('gray')
                    ->visible(fn (Ticket $t) => $t->state instanceof InProgress || $t->state instanceof Assigned)
                    ->action(function (Ticket $t) {
                        app(TransitionTicketState::class)($t, WaitingCustomer::class);
                    }),
                Action::make('resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Ticket $t) => ! ($t->state instanceof Resolved || $t->state instanceof Closed || $t->state instanceof Cancelled || $t->state instanceof NewState))
                    ->action(function (Ticket $t) {
                        app(TransitionTicketState::class)($t, Resolved::class);
                    }),
                Action::make('close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->visible(fn (Ticket $t) => $t->state instanceof Resolved)
                    ->requiresConfirmation()
                    ->action(function (Ticket $t) {
                        app(TransitionTicketState::class)($t, Closed::class);
                    }),
                Action::make('cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Ticket $t) => ! ($t->state instanceof Closed || $t->state instanceof Cancelled))
                    ->requiresConfirmation()
                    ->action(function (Ticket $t) {
                        app(TransitionTicketState::class)($t, Cancelled::class);
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
