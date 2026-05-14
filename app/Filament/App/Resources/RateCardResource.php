<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\RateCardResource\Pages;
use App\Models\RateCard;
use Filament\Forms\Components\DatePicker;
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

class RateCardResource extends Resource
{
    protected static ?string $model = RateCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Costing';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Rate card')
                ->columns(2)
                ->schema([
                    TextInput::make('name.en')->label('Name (EN)')->required()->maxLength(255),
                    TextInput::make('name.ar')->label('Name (AR)')->maxLength(255),
                    Select::make('visit_type')
                        ->options([
                            'online' => 'Online',
                            'offline' => 'Onsite',
                            'any' => 'Any',
                        ])
                        ->default('any')
                        ->required(),
                    Select::make('technician_seniority')
                        ->options([
                            'junior' => 'Junior',
                            'mid' => 'Mid',
                            'senior' => 'Senior',
                            'any' => 'Any',
                        ])
                        ->default('any')
                        ->required(),
                    TextInput::make('hourly_rate_minor')
                        ->label('Hourly rate (minor units)')
                        ->numeric()
                        ->required()
                        ->helperText('Stored in minor units (e.g. piastres). 5000 = 50.00 EGP.'),
                    TextInput::make('currency')->default('EGP')->maxLength(3)->required(),
                    TextInput::make('minimum_charge_minor')
                        ->label('Minimum charge (minor units)')
                        ->numeric()
                        ->default(0),
                    TextInput::make('billing_increment_minutes')
                        ->label('Billing increment (minutes)')
                        ->numeric()
                        ->default(15)
                        ->minValue(1),
                    DatePicker::make('valid_from')->native(false),
                    DatePicker::make('valid_to')->native(false),
                    Toggle::make('is_default')
                        ->label('Default rate card')
                        ->helperText('Only one rate card can be the default per org.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->formatStateUsing(fn ($s, RateCard $r) => $r->getTranslation('name', 'en')),
                TextColumn::make('visit_type')->badge(),
                TextColumn::make('technician_seniority')->label('Seniority')->badge(),
                TextColumn::make('hourly_rate_minor')
                    ->label('Hourly rate')
                    ->formatStateUsing(fn ($s, RateCard $r) => number_format($r->hourly_rate_minor / 100, 2).' '.$r->currency),
                TextColumn::make('billing_increment_minutes')->label('Increment')->suffix(' min')->toggleable(),
                TextColumn::make('valid_from')->date()->toggleable(),
                TextColumn::make('valid_to')->date()->toggleable(),
                IconColumn::make('is_default')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRateCards::route('/'),
            'create' => Pages\CreateRateCard::route('/create'),
            'edit' => Pages\EditRateCard::route('/{record}/edit'),
        ];
    }
}
