<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TravelZoneResource\Pages;
use App\Models\TravelZone;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TravelZoneResource extends Resource
{
    protected static ?string $model = TravelZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Costing';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Travel zone')
                ->columns(2)
                ->schema([
                    TextInput::make('name.en')->label('Name (EN)')->required()->maxLength(255),
                    TextInput::make('name.ar')->label('Name (AR)')->maxLength(255),
                    TextInput::make('flat_fee_minor')
                        ->label('Flat fee (minor units)')
                        ->numeric()
                        ->required()
                        ->helperText('Stored in minor units. 5000 = 50.00 EGP.'),
                    TextInput::make('currency')->default('EGP')->maxLength(3)->required(),
                    Textarea::make('description')->columnSpanFull()->rows(2),
                    Textarea::make('coverage_areas')
                        ->columnSpanFull()
                        ->label('Coverage areas (one per line)')
                        ->rows(4)
                        ->dehydrateStateUsing(fn ($state) => is_string($state)
                            ? array_values(array_filter(array_map('trim', explode("\n", $state))))
                            : (array) $state)
                        ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : (string) $state),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->formatStateUsing(fn ($s, TravelZone $z) => $z->getTranslation('name', 'en')),
                TextColumn::make('flat_fee_minor')
                    ->label('Flat fee')
                    ->formatStateUsing(fn ($s, TravelZone $z) => number_format($z->flat_fee_minor / 100, 2).' '.$z->currency),
                TextColumn::make('coverage_areas')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->wrap()
                    ->toggleable(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTravelZones::route('/'),
            'create' => Pages\CreateTravelZone::route('/create'),
            'edit' => Pages\EditTravelZone::route('/{record}/edit'),
        ];
    }
}
