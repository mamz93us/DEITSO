<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TicketResource\RelationManagers;

use App\Actions\Tickets\AddCommentToTicket;
use App\Models\Ticket;
use App\Models\TicketComment;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    protected static ?string $icon = 'heroicon-o-chat-bubble-left-right';

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('body')->required()->rows(4),
            Toggle::make('is_internal')
                ->label('Internal — hidden from the customer')
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')->label('Author'),
                BadgeColumn::make('author_type'),
                TextColumn::make('body')->wrap()->limit(150),
                IconColumn::make('is_internal')->boolean()->label('Internal'),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        /** @var Ticket $owner */
                        $owner = $this->getOwnerRecord();

                        return app(AddCommentToTicket::class)(
                            $owner,
                            auth()->user(),
                            $data['body'],
                            TicketComment::AUTHOR_STAFF,
                            (bool) ($data['is_internal'] ?? false),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
