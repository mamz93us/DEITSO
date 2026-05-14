<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MailServerResource\Pages;
use App\Models\MailServer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailServerResource extends Resource
{
    protected static ?string $model = MailServer::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Email';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('WHM / cPanel server')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpan(1),
                    Select::make('status')
                        ->options([
                            MailServer::STATUS_ACTIVE => 'Active',
                            MailServer::STATUS_DISABLED => 'Disabled',
                            MailServer::STATUS_FAILED => 'Failed',
                        ])
                        ->default(MailServer::STATUS_ACTIVE)
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('hostname')->required()->placeholder('whm.example.com')->columnSpan(1),
                    TextInput::make('port')->numeric()->default(2087)->columnSpan(1),
                    TextInput::make('username')->required()->placeholder('root')->columnSpan(1),
                    TextInput::make('api_token_encrypted')
                        ->label('API token')
                        ->password()
                        ->revealable()
                        ->helperText('Encrypted at rest.')
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('hostname')->copyable(),
                TextColumn::make('port'),
                BadgeColumn::make('status')->colors([
                    'success' => MailServer::STATUS_ACTIVE,
                    'gray' => MailServer::STATUS_DISABLED,
                    'danger' => MailServer::STATUS_FAILED,
                ]),
                TextColumn::make('domains_count')->counts('domains')->label('Domains'),
                TextColumn::make('last_connection_check_at')->dateTime()->since()->toggleable(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailServers::route('/'),
            'create' => Pages\CreateMailServer::route('/create'),
            'edit' => Pages\EditMailServer::route('/{record}/edit'),
        ];
    }
}
