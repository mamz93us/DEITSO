<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetResource\RelationManagers;

use App\Models\AssetAssignment;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Assignment history';

    protected static ?string $icon = 'heroicon-o-clipboard-document-list';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('from_at')->dateTime()->sortable()->label('From'),
                TextColumn::make('to_at')->dateTime()->placeholder('current')->label('To'),
                TextColumn::make('assigned_to_type')->badge()->label('Type'),
                TextColumn::make('holder_name')
                    ->label('Holder')
                    ->state(function (AssetAssignment $r) {
                        return match ($r->assigned_to_type) {
                            AssetAssignment::TYPE_EMPLOYEE => optional(Employee::find($r->assigned_to_id))->full_name,
                            AssetAssignment::TYPE_BRANCH => optional(Branch::find($r->assigned_to_id))->name,
                            default => $r->assigned_to_id,
                        } ?? '—';
                    }),
                TextColumn::make('quantity'),
                TextColumn::make('reason')->toggleable(),
                TextColumn::make('assignedBy.name')->label('By')->toggleable(),
                IconColumn::make('is_current')
                    ->boolean()
                    ->state(fn (AssetAssignment $r) => $r->to_at === null)
                    ->label('Current'),
            ])
            ->defaultSort('from_at', 'desc');
    }
}
