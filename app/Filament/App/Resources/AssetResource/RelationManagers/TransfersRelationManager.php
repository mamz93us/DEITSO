<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetResource\RelationManagers;

use App\Models\AssetTransfer;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'transfers';

    protected static ?string $title = 'Transfers';

    protected static ?string $icon = 'heroicon-o-arrows-right-left';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->label('Requested'),
                TextColumn::make('fromEmployee.first_name')
                    ->label('From')
                    ->formatStateUsing(fn ($state, AssetTransfer $r) => $r->fromEmployee?->full_name ?? $r->fromBranch?->name ?? '—'),
                TextColumn::make('toEmployee.first_name')
                    ->label('To')
                    ->formatStateUsing(fn ($state, AssetTransfer $r) => $r->toEmployee?->full_name ?? $r->toBranch?->name ?? '—'),
                BadgeColumn::make('state')
                    ->getStateUsing(fn (AssetTransfer $r) => $r->state?->label() ?? '—')
                    ->colors([
                        'gray' => 'Draft',
                        'warning' => 'Pending approval',
                        'primary' => 'Approved',
                        'success' => 'Completed',
                        'danger' => 'Rejected',
                    ]),
                TextColumn::make('reason')->toggleable(),
                TextColumn::make('transferred_at')->dateTime()->placeholder('—')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
