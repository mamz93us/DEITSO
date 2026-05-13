<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Reports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetInventoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Asset inventory';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.app.pages.reports.table-report';

    public function getTitle(): string
    {
        return 'Asset inventory';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Asset::query()
                ->with(['category', 'branch', 'assignedEmployee', 'supplier']))
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('name')
                    ->formatStateUsing(fn (Asset $a) => $a->name ?: ($a->assetModel?->model_name ?? '—'))
                    ->label('Name'),
                TextColumn::make('serial_number')->toggleable(),
                BadgeColumn::make('status')->colors([
                    'success' => Asset::STATUS_IN_STOCK,
                    'primary' => Asset::STATUS_DEPLOYED,
                    'warning' => [Asset::STATUS_IN_MAINTENANCE, Asset::STATUS_RETIRED],
                    'danger' => [Asset::STATUS_SCRAPPED, Asset::STATUS_LOST],
                ]),
                TextColumn::make('branch.name')->label('Branch')->toggleable(),
                TextColumn::make('assignedEmployee.first_name')
                    ->label('Holder')
                    ->formatStateUsing(fn ($s, Asset $a) => $a->assignedEmployee?->full_name ?? '—')
                    ->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->toggleable(),
                TextColumn::make('purchase_cost_minor')
                    ->label('Cost')
                    ->money(fn (Asset $a) => $a->currency ?: 'EGP', divideBy: 100)
                    ->toggleable(),
                TextColumn::make('warranty_until')->date()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Asset::STATUS_IN_STOCK => 'In stock',
                    Asset::STATUS_DEPLOYED => 'Deployed',
                    Asset::STATUS_IN_MAINTENANCE => 'In maintenance',
                    Asset::STATUS_RETIRED => 'Retired',
                    Asset::STATUS_SCRAPPED => 'Scrapped',
                    Asset::STATUS_LOST => 'Lost',
                ]),
                SelectFilter::make('category_id')->label('Category')
                    ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name])),
                SelectFilter::make('branch_id')->label('Branch')
                    ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name])),
            ])
            ->defaultSort('code', 'desc')
            ->paginationPageOptions([25, 50, 100, 250]);
    }
}
