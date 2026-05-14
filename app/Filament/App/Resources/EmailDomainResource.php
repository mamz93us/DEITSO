<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmailDomainResource\Pages;
use App\Models\EmailDomain;
use App\Models\MailServer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailDomainResource extends Resource
{
    protected static ?string $model = EmailDomain::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Email';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Email domain')
                ->columns(2)
                ->schema([
                    Select::make('mail_server_id')
                        ->label('Mail server')
                        ->options(fn () => MailServer::query()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('domain')
                        ->required()
                        ->placeholder('example.com')
                        ->columnSpan(1),
                    Toggle::make('is_active')->default(true)->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->searchable()->copyable(),
                TextColumn::make('mailServer.name')->label('Mail server'),
                TextColumn::make('accounts_count')->counts('accounts')->label('Accounts'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailDomains::route('/'),
            'create' => Pages\CreateEmailDomain::route('/create'),
            'edit' => Pages\EditEmailDomain::route('/{record}/edit'),
        ];
    }
}
