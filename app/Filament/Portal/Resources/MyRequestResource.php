<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\MyRequestResource\Pages;
use App\Models\EmployeeRequest;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only view of the requests this employee has raised. (Creation of new
 * requests in the portal lands in a later iteration; for now the org-side
 * EmployeeRequestResource handles request creation.)
 */
class MyRequestResource extends Resource
{
    protected static ?string $model = EmployeeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'My requests';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        $employee = auth()->user()?->employee;

        return parent::getEloquentQuery()
            ->when($employee, fn ($q) => $q->where('requester_employee_id', $employee->id))
            ->when(! $employee, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge(),
                TextColumn::make('title')->limit(40),
                BadgeColumn::make('type')->formatStateUsing(fn ($s) => str_replace('_', ' ', $s)),
                BadgeColumn::make('urgency'),
                BadgeColumn::make('state')->getStateUsing(fn (EmployeeRequest $r) => $r->state?->label() ?? '—'),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
