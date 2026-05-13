<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SupplierResource extends Resource
{
    use Translatable;

    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'ITAM';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Supplier')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->helperText('Switch locale top-right to enter Arabic.')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $get('code') && is_string($state) && $state !== '') {
                                $set('code', strtoupper(Str::slug(Str::limit($state, 12, ''), '')));
                            }
                        })
                        ->columnSpan(1),

                    TextInput::make('code')
                        ->label('Short code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(32)
                        ->columnSpan(1),

                    Select::make('status')
                        ->options([
                            Supplier::STATUS_ACTIVE => 'Active',
                            Supplier::STATUS_INACTIVE => 'Inactive',
                        ])
                        ->default(Supplier::STATUS_ACTIVE)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('payment_terms')
                        ->placeholder('e.g. Net 30')
                        ->maxLength(64)
                        ->columnSpan(1),
                ]),

            Section::make('Contact')
                ->columns(2)
                ->schema([
                    TextInput::make('contact_person')->maxLength(255)->columnSpan(1),
                    TextInput::make('email')->email()->maxLength(255)->columnSpan(1),
                    TextInput::make('phone')->tel()->maxLength(32)->columnSpan(1),
                    TextInput::make('mobile')->tel()->maxLength(32)->columnSpan(1),

                    KeyValue::make('address')
                        ->keyLabel('Field')
                        ->valueLabel('Value')
                        ->keyPlaceholder('street, city, country, postal_code')
                        ->columnSpanFull(),
                ]),

            Section::make('Legal & notes')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('tax_number')->maxLength(64)->columnSpan(1),
                    TextInput::make('commercial_registration_number')->label('Commercial registration #')->maxLength(64)->columnSpan(1),
                    Textarea::make('notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('contact_person')->toggleable(),
                TextColumn::make('email')->toggleable()->copyable(),
                TextColumn::make('phone')->toggleable(),
                TextColumn::make('payment_terms')->toggleable(),
                TextColumn::make('assets_count')->counts('assets')->label('Assets')->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => Supplier::STATUS_ACTIVE,
                        'gray' => Supplier::STATUS_INACTIVE,
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Supplier::STATUS_ACTIVE => 'Active',
                        Supplier::STATUS_INACTIVE => 'Inactive',
                    ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
