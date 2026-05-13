<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssetCategoryResource\Pages;
use App\Models\AssetCategory;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

class AssetCategoryResource extends Resource
{
    use Translatable;

    protected static ?string $model = AssetCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'ITAM';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Categories';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Category')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->helperText('Switch locale top-right to enter Arabic.')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('code')
                        ->label('Short code')
                        ->helperText('Used in asset codes, e.g. LAP for laptops.')
                        ->required()
                        ->alphaDash()
                        ->maxLength(32)
                        ->columnSpan(1),

                    Select::make('parent_id')
                        ->label('Parent category')
                        ->options(fn ($livewire) => AssetCategory::query()
                            ->when($livewire->record ?? null, fn ($q, $rec) => $q->whereKeyNot($rec))
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('tracking_mode')
                        ->options([
                            AssetCategory::TRACKING_SERIALIZED => 'Serialized (one row per unit)',
                            AssetCategory::TRACKING_BULK => 'Bulk (one row + quantity)',
                            AssetCategory::TRACKING_LICENSE => 'License (seats + expiry)',
                        ])
                        ->default(AssetCategory::TRACKING_SERIALIZED)
                        ->required()
                        ->columnSpan(1),
                ]),

            Section::make('Custom-fields schema')
                ->description('Fields that assets in this category will collect, beyond the standard ones.')
                ->collapsed()
                ->schema([
                    KeyValue::make('custom_fields_schema')
                        ->keyLabel('Field key')
                        ->valueLabel('Type / description')
                        ->keyPlaceholder('e.g. screen_size, os_version')
                        ->valuePlaceholder('text, number, date, …')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('parent.name')->label('Parent')->placeholder('— Top level')->toggleable(),
                BadgeColumn::make('tracking_mode')
                    ->colors([
                        'primary' => AssetCategory::TRACKING_SERIALIZED,
                        'warning' => AssetCategory::TRACKING_BULK,
                        'success' => AssetCategory::TRACKING_LICENSE,
                    ]),
                TextColumn::make('assets_count')->counts('assets')->label('Assets')->sortable(),
            ])
            ->filters([
                SelectFilter::make('tracking_mode')->options([
                    AssetCategory::TRACKING_SERIALIZED => 'Serialized',
                    AssetCategory::TRACKING_BULK => 'Bulk',
                    AssetCategory::TRACKING_LICENSE => 'License',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetCategories::route('/'),
            'create' => Pages\CreateAssetCategory::route('/create'),
            'edit' => Pages\EditAssetCategory::route('/{record}/edit'),
        ];
    }
}
