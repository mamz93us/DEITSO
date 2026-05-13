<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssetModelResource\Pages;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Supplier;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetModelResource extends Resource
{
    protected static ?string $model = AssetModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'ITAM';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Asset models';

    protected static ?string $modelLabel = 'Asset model';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Model')
                ->columns(2)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('image')
                        ->collection('image')
                        ->image()
                        ->imageEditor()
                        ->columnSpan(1),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->required()
                        ->searchable()
                        ->columnSpan(1),

                    TextInput::make('manufacturer')->maxLength(255)->columnSpan(1),
                    TextInput::make('model_name')->required()->maxLength(255)->columnSpan(1),

                    Toggle::make('is_active')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),

                    Select::make('preferred_supplier_id')
                        ->label('Preferred supplier')
                        ->options(fn () => Supplier::query()->where('status', Supplier::STATUS_ACTIVE)->get()->mapWithKeys(fn ($s) => [$s->id => $s->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),
                ]),

            Section::make('Specs')
                ->description('Free-form spec attributes (cpu, ram, storage, screen_size, os, etc.)')
                ->schema([
                    KeyValue::make('specs')
                        ->keyLabel('Spec')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ]),

            Section::make('Default pricing')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('default_unit_cost_minor')
                        ->label('Default unit cost (minor units)')
                        ->helperText('Stored as integer minor units. e.g. 1500000 = 15,000.00 EGP')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    TextInput::make('currency')
                        ->default('EGP')
                        ->maxLength(3)
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->collection('image')->square(),
                TextColumn::make('manufacturer')->searchable()->sortable(),
                TextColumn::make('model_name')->label('Model')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('preferredSupplier.name')->label('Preferred supplier')->toggleable(),
                TextColumn::make('assets_count')->counts('assets')->label('Assets')->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name])),
                SelectFilter::make('is_active')->options([1 => 'Active', 0 => 'Inactive']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('manufacturer');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetModels::route('/'),
            'create' => Pages\CreateAssetModel::route('/create'),
            'edit' => Pages\EditAssetModel::route('/{record}/edit'),
        ];
    }
}
