<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    use Translatable;

    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Branch')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Branch name')
                        ->helperText('Use the locale switcher (top-right) to enter Arabic.')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('code')
                        ->label('Code')
                        ->helperText('Short code used in asset IDs, e.g. CAI, ALX')
                        ->required()
                        ->alphaDash()
                        ->maxLength(32)
                        ->columnSpan(1),

                    Toggle::make('is_primary')
                        ->label('Primary branch')
                        ->helperText('Marks this as the organization\'s main branch')
                        ->inline(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Address & coordinates')
                ->columns(2)
                ->collapsed()
                ->schema([
                    KeyValue::make('address')
                        ->keyLabel('Field')
                        ->valueLabel('Value')
                        ->keyPlaceholder('street, city, country, postal_code')
                        ->columnSpanFull(),

                    TextInput::make('lat')
                        ->label('Latitude')
                        ->numeric()
                        ->step('0.0000001')
                        ->columnSpan(1),

                    TextInput::make('lng')
                        ->label('Longitude')
                        ->numeric()
                        ->step('0.0000001')
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->badge()->searchable(),
                IconColumn::make('is_primary')->boolean()->sortable()->label('Primary'),
                TextColumn::make('lat')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lng')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_primary', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
