<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Reports;

use App\Models\Asset;
use App\Models\AssetCategory;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenseSeatUtilizationReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'License seat utilization';

    protected static ?int $navigationSort = 55;

    protected static string $view = 'filament.app.pages.reports.table-report';

    public function getTitle(): string
    {
        return 'License seat utilization';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Asset::query()
                ->where('tracking_mode', AssetCategory::TRACKING_LICENSE)
                ->withCount(['assignments as seats_used_count' => function ($q) {
                    $q->whereNull('to_at');
                }])
                ->with(['category', 'supplier']))
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('name')->label('License'),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('supplier.name')->label('Vendor')->toggleable(),
                TextColumn::make('seats_total')->label('Total seats')->placeholder('∞'),
                TextColumn::make('seats_used_count')->label('In use')->sortable(),
                TextColumn::make('utilization_pct')
                    ->label('Utilization')
                    ->state(fn (Asset $a) => $a->seats_total
                        ? round(($a->seats_used_count / max(1, $a->seats_total)) * 100).'%'
                        : '—'),
                BadgeColumn::make('status_flag')
                    ->label('Status')
                    ->state(function (Asset $a) {
                        if (! $a->seats_total) {
                            return 'unlimited';
                        }
                        $pct = ($a->seats_used_count / $a->seats_total) * 100;

                        return $pct >= 100 ? 'full' : ($pct >= 80 ? 'high' : 'ok');
                    })
                    ->colors([
                        'gray' => 'unlimited',
                        'success' => 'ok',
                        'warning' => 'high',
                        'danger' => 'full',
                    ]),
            ])
            ->defaultSort('seats_used_count', 'desc');
    }
}
