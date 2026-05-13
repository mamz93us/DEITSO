<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Actions\Tickets\AddCommentToTicket;
use App\Filament\Portal\Resources\MyTicketResource\Pages;
use App\Filament\Portal\Resources\MyTicketResource\RelationManagers\PublicCommentsRelationManager;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The portal's My Tickets surface. Users see only tickets they raised, can
 * create new tickets, and can comment on their own. Internal staff comments
 * are hidden via the table query.
 */
class MyTicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'My tickets';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('requester_user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('New ticket')
                ->columns(2)
                ->schema([
                    TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                    Select::make('category_id')
                        ->options(fn () => TicketCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->searchable()
                        ->nullable(),
                    Select::make('priority')
                        ->options([
                            Ticket::PRIORITY_LOW => 'Low',
                            Ticket::PRIORITY_NORMAL => 'Normal',
                            Ticket::PRIORITY_HIGH => 'High',
                            Ticket::PRIORITY_URGENT => 'Urgent',
                        ])
                        ->default(Ticket::PRIORITY_NORMAL)
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('subject')->limit(40),
                BadgeColumn::make('priority'),
                BadgeColumn::make('state')
                    ->getStateUsing(fn (Ticket $t) => $t->state?->label() ?? '—'),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('—'),
                TextColumn::make('created_at')->since(),
            ])
            ->actions([
                Action::make('comment')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Textarea::make('body')->required()->rows(3)->placeholder('Reply to support...'),
                    ])
                    ->action(function (Ticket $t, array $data) {
                        app(AddCommentToTicket::class)(
                            $t,
                            auth()->user(),
                            $data['body'],
                            TicketComment::AUTHOR_END_USER,
                            false,
                        );
                        Notification::make()->title('Comment posted')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PublicCommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyTickets::route('/'),
            'create' => Pages\CreateMyTicket::route('/create'),
            'view' => Pages\ViewMyTicket::route('/{record}'),
        ];
    }
}
