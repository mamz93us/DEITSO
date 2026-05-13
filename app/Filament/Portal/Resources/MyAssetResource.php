<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\MyAssetResource\Pages;
use App\Models\Asset;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only view of assets currently assigned to this employee.
 */
class MyAssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'My assets';

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        $employee = auth()->user()?->employee;

        return parent::getEloquentQuery()
            ->when($employee, fn ($q) => $q->where('assigned_employee_id', $employee->id))
            ->when(! $employee, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge(),
                TextColumn::make('name')
                    ->formatStateUsing(fn (Asset $a) => $a->name ?: ($a->assetModel?->model_name ?? '—'))
                    ->label('Name'),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('serial_number')->label('S/N')->copyable(),
                BadgeColumn::make('status'),
                TextColumn::make('warranty_until')->date(),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyAssets::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
