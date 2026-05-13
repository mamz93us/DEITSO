<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Reports;

use App\Models\Asset;
use App\Models\AssetCategory;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate report: total purchase value per asset category, separated by
 * active vs scrapped, with item counts.
 */
class AssetValuationReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Asset valuation';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.app.pages.reports.table-report';

    public function getTitle(): string
    {
        return 'Asset valuation by category';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                /** @var QueryBuilder $sub */
                $sub = DB::table('assets')
                    ->whereNull('assets.deleted_at')
                    ->selectRaw('
                        category_id,
                        COUNT(*) as item_count,
                        SUM(CASE WHEN status NOT IN ("scrapped","lost") THEN 1 ELSE 0 END) as active_count,
                        SUM(CASE WHEN status NOT IN ("scrapped","lost") THEN COALESCE(purchase_cost_minor,0) ELSE 0 END) as active_value_minor,
                        SUM(CASE WHEN status IN ("scrapped","lost") THEN COALESCE(purchase_cost_minor,0) ELSE 0 END) as scrapped_value_minor,
                        MAX(currency) as currency
                    ')
                    ->groupBy('category_id');

                // Wrap the aggregation in AssetCategory so Filament has an Eloquent builder.
                return AssetCategory::query()
                    ->joinSub($sub, 'agg', 'agg.category_id', '=', 'asset_categories.id')
                    ->selectRaw('asset_categories.*, agg.item_count, agg.active_count, agg.active_value_minor, agg.scrapped_value_minor, agg.currency');
            })
            ->columns([
                TextColumn::make('name')->label('Category')->searchable(),
                TextColumn::make('code')->badge(),
                TextColumn::make('item_count')->label('Total items')->sortable(),
                TextColumn::make('active_count')->label('Active'),
                TextColumn::make('active_value_minor')
                    ->label('Active value')
                    ->money(fn ($r) => $r->currency ?? 'EGP', divideBy: 100)
                    ->sortable(),
                TextColumn::make('scrapped_value_minor')
                    ->label('Scrapped value')
                    ->money(fn ($r) => $r->currency ?? 'EGP', divideBy: 100)
                    ->toggleable(),
            ])
            ->defaultSort('active_value_minor', 'desc')
            ->paginationPageOptions([25, 50]);
    }
}
