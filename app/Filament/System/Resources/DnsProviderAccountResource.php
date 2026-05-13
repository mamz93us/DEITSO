<?php

declare(strict_types=1);

namespace App\Filament\System\Resources;

use App\Filament\System\Resources\DnsProviderAccountResource\Pages;
use App\Models\DnsProviderAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DnsProviderAccountResource extends Resource
{
    protected static ?string $model = DnsProviderAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('provider')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_domain')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('credentials_encrypted')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('environment')
                    ->required(),
                Forms\Components\Toggle::make('is_default')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_check_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_domain')
                    ->searchable(),
                Tables\Columns\TextColumn::make('environment'),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('last_check_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDnsProviderAccounts::route('/'),
            'create' => Pages\CreateDnsProviderAccount::route('/create'),
            'edit' => Pages\EditDnsProviderAccount::route('/{record}/edit'),
        ];
    }
}
