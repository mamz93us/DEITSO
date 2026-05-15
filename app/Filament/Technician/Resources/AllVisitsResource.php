<?php

declare(strict_types=1);

namespace App\Filament\Technician\Resources;

use App\Filament\Technician\Resources\AllVisitsResource\Pages;
use App\Models\Visit;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cross-org visit queue for technicians. OrganizationScope skips its filter for
 * technicians so this returns visits across every org.
 */
class AllVisitsResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Work queue';

    protected static ?string $navigationLabel = 'All visits';

    protected static ?string $modelLabel = 'Visit';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'code';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('organization.slug')->label('Org')->badge()->color('info'),
                BadgeColumn::make('type'),
                BadgeColumn::make('state')->getStateUsing(fn (Visit $v) => $v->state?->label() ?? '—'),
                TextColumn::make('technician.name')->label('Tech')->toggleable(),
                TextColumn::make('customerBranch.name')->label('Site')->toggleable(),
                TextColumn::make('scheduled_at')->dateTime()->sortable(),
                TextColumn::make('duration_minutes')->label('Mins')->toggleable(),
                IconColumn::make('is_billable')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('mine')
                    ->label('Assigned to me')
                    ->query(fn (Builder $q) => $q->where('technician_user_id', auth()->id())),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllVisits::route('/'),
        ];
    }
}
