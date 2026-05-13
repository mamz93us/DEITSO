<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Reports;

use App\Models\Supplier;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SpendBySupplierReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Spend by supplier';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.app.pages.reports.table-report';

    public function getTitle(): string
    {
        return 'Spend by supplier';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $sub = DB::table('assets')
                    ->whereNull('assets.deleted_at')
                    ->whereNotNull('supplier_id')
                    ->selectRaw('
                        supplier_id,
                        COUNT(*) as items_supplied,
                        SUM(COALESCE(purchase_cost_minor, 0)) as total_spend_minor,
                        MAX(currency) as currency
                    ')
                    ->groupBy('supplier_id');

                return Supplier::query()
                    ->joinSub($sub, 'agg', 'agg.supplier_id', '=', 'suppliers.id')
                    ->selectRaw('suppliers.*, agg.items_supplied, agg.total_spend_minor, agg.currency');
            })
            ->columns([
                TextColumn::make('code')->badge(),
                TextColumn::make('name')->label('Supplier')->searchable()->sortable(),
                TextColumn::make('contact_person')->toggleable(),
                BadgeColumn::make('status')->colors([
                    'success' => Supplier::STATUS_ACTIVE,
                    'gray' => Supplier::STATUS_INACTIVE,
                ]),
                TextColumn::make('items_supplied')->label('Items')->sortable(),
                TextColumn::make('total_spend_minor')
                    ->label('Total spend')
                    ->money(fn ($r) => $r->currency ?? 'EGP', divideBy: 100)
                    ->sortable(),
            ])
            ->defaultSort('total_spend_minor', 'desc')
            ->paginationPageOptions([25, 50, 100]);
    }
}
