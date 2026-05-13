<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Reports;

use App\Models\AssetScrap;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScrappedAssetsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Scrapped assets';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.app.pages.reports.table-report';

    public function getTitle(): string
    {
        return 'Scrapped assets';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AssetScrap::query()->with(['asset.category', 'approvedBy']))
            ->columns([
                TextColumn::make('asset.code')->label('Asset code')->badge()->searchable(),
                TextColumn::make('asset.category.name')->label('Category')->toggleable(),
                BadgeColumn::make('reason')->colors([
                    'gray' => AssetScrap::REASON_END_OF_LIFE,
                    'warning' => AssetScrap::REASON_DAMAGED,
                    'danger' => AssetScrap::REASON_LOST,
                    'success' => [AssetScrap::REASON_SOLD, AssetScrap::REASON_DONATED],
                    'primary' => AssetScrap::REASON_OTHER,
                ]),
                TextColumn::make('disposal_method')->toggleable(),
                TextColumn::make('residual_value_minor')
                    ->label('Residual value')
                    ->money(fn ($r) => $r->currency ?? 'EGP', divideBy: 100)
                    ->toggleable(),
                TextColumn::make('approvedBy.name')->label('Approved by')->toggleable(),
                TextColumn::make('approved_at')->dateTime()->sortable(),
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
            ->defaultSort('approved_at', 'desc');
    }
}
