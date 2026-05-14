<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssetScrapResource\Pages;
use App\Models\Asset;
use App\Models\AssetScrap;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetScrapResource extends Resource
{
    protected static ?string $model = AssetScrap::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationGroup = 'ITAM';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Scrap record';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Scrap record')
                ->columns(2)
                ->schema([
                    Select::make('asset_id')
                        ->label('Asset')
                        ->options(fn () => Asset::query()->limit(200)->pluck('code', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('reason')
                        ->options([
                            AssetScrap::REASON_END_OF_LIFE => 'End of life',
                            AssetScrap::REASON_DAMAGED => 'Damaged',
                            AssetScrap::REASON_LOST => 'Lost',
                            AssetScrap::REASON_SOLD => 'Sold',
                            AssetScrap::REASON_DONATED => 'Donated',
                            AssetScrap::REASON_OTHER => 'Other',
                        ])
                        ->required(),
                    TextInput::make('disposal_method')->maxLength(255)->placeholder('e.g. Recycler X'),
                    TextInput::make('residual_value_minor')
                        ->label('Residual value (minor units)')
                        ->numeric()
                        ->default(0),
                    TextInput::make('currency')->default('EGP')->maxLength(3),
                    Select::make('approved_by_user_id')
                        ->label('Approved by')
                        ->options(fn () => User::query()->limit(200)->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    DateTimePicker::make('approved_at')->native(false)->nullable(),
                    Textarea::make('notes')->columnSpanFull()->rows(3),
                    SpatieMediaLibraryFileUpload::make('evidence')
                        ->collection('evidence')
                        ->label('Evidence (photos / certificate of destruction)')
                        ->multiple()
                        ->image()
                        ->columnSpanFull()
                        ->maxFiles(10),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.code')->label('Asset')->searchable(),
                BadgeColumn::make('reason')->colors([
                    'gray' => AssetScrap::REASON_END_OF_LIFE,
                    'danger' => AssetScrap::REASON_DAMAGED,
                    'warning' => AssetScrap::REASON_LOST,
                    'success' => [AssetScrap::REASON_SOLD, AssetScrap::REASON_DONATED],
                ]),
                TextColumn::make('disposal_method')->toggleable(),
                TextColumn::make('residual_value_minor')
                    ->label('Residual')
                    ->formatStateUsing(fn ($s, AssetScrap $r) => number_format(((int) $r->residual_value_minor) / 100, 2).' '.($r->currency ?? 'EGP'))
                    ->toggleable(),
                TextColumn::make('approvedBy.name')->label('Approved by')->toggleable(),
                TextColumn::make('approved_at')->dateTime()->since()->toggleable(),
                SpatieMediaLibraryImageColumn::make('evidence')
                    ->collection('evidence')
                    ->circular()
                    ->stacked()
                    ->limit(3),
            ])
            ->filters([
                SelectFilter::make('reason')->options([
                    AssetScrap::REASON_END_OF_LIFE => 'End of life',
                    AssetScrap::REASON_DAMAGED => 'Damaged',
                    AssetScrap::REASON_LOST => 'Lost',
                    AssetScrap::REASON_SOLD => 'Sold',
                    AssetScrap::REASON_DONATED => 'Donated',
                    AssetScrap::REASON_OTHER => 'Other',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetScraps::route('/'),
            'create' => Pages\CreateAssetScrap::route('/create'),
            'edit' => Pages\EditAssetScrap::route('/{record}/edit'),
        ];
    }
}
