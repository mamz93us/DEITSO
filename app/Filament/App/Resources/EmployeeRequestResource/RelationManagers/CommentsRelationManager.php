<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmployeeRequestResource\RelationManagers;

use App\Actions\Requests\AddCommentToRequest;
use App\Models\EmployeeRequest;
use App\Models\EmployeeRequestComment;
use Filament\Forms\Components\Select;
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
            Textarea::make('body')->required()->rows(3),
            Select::make('author_role')
                ->options([
                    EmployeeRequestComment::AUTHOR_REQUESTER => 'Requester',
                    EmployeeRequestComment::AUTHOR_MANAGER => 'Manager',
                    EmployeeRequestComment::AUTHOR_ADMIN => 'Admin',
                    EmployeeRequestComment::AUTHOR_PROCUREMENT => 'Procurement',
                ])
                ->default(EmployeeRequestComment::AUTHOR_ADMIN)
                ->required(),
            Toggle::make('is_internal')
                ->label('Internal — hidden from the requester')
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')->label('Author')->toggleable(),
                BadgeColumn::make('author_role'),
                TextColumn::make('body')->wrap()->limit(120),
                IconColumn::make('is_internal')->boolean()->label('Internal'),
                TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add comment')
                    ->using(function (array $data) {
                        /** @var EmployeeRequest $owner */
                        $owner = $this->getOwnerRecord();

                        return app(AddCommentToRequest::class)(
                            $owner,
                            auth()->user(),
                            $data['body'],
                            $data['author_role'],
                            (bool) ($data['is_internal'] ?? false),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
