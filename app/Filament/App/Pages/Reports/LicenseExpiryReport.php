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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenseExpiryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'License expiry';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.app.pages.reports.table-report';

    public function getTitle(): string
    {
        return 'License expiry — expired & expiring within 90 days';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Asset::query()
                ->where('tracking_mode', AssetCategory::TRACKING_LICENSE)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(90))
                ->with(['category', 'supplier']))
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('name')->label('License'),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('supplier.name')->label('Vendor')->toggleable(),
                TextColumn::make('expiry_date')
                    ->date()
                    ->color(fn (Asset $a) => $a->is_expired ? 'danger' : 'warning')
                    ->sortable(),
                TextColumn::make('days_left')
                    ->label('Days')
                    ->state(fn (Asset $a) => $a->expiry_date
                        ? (int) round(now()->diffInDays($a->expiry_date, false))
                        : null),
                BadgeColumn::make('renewable')
                    ->state(fn (Asset $a) => $a->auto_renewal ? 'auto-renews' : ($a->renewable ? 'renewable' : 'one-time'))
                    ->colors([
                        'success' => 'auto-renews',
                        'primary' => 'renewable',
                        'gray' => 'one-time',
                    ]),
                TextColumn::make('seats_used')
                    ->label('Seats')
                    ->state(fn (Asset $a) => $a->seats_used.' / '.($a->seats_total ?? '∞')),
            ])
            ->filters([
                Filter::make('only_expired')
                    ->label('Only expired')
                    ->query(fn (Builder $q) => $q->where('expiry_date', '<', now())),
                Filter::make('within_30_days')
                    ->label('Within 30 days')
                    ->query(fn (Builder $q) => $q->where('expiry_date', '<=', now()->addDays(30))),
            ])
            ->defaultSort('expiry_date');
    }
}
