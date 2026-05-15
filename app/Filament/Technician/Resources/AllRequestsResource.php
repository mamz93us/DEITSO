<?php

declare(strict_types=1);

namespace App\Filament\Technician\Resources;

use App\Filament\Technician\Resources\AllRequestsResource\Pages;
use App\Models\EmployeeRequest;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Cross-org employee-request queue for technicians. Useful to see procurement
 * + access requests that translate into hardware visits.
 */
class AllRequestsResource extends Resource
{
    protected static ?string $model = EmployeeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Work queue';

    protected static ?string $navigationLabel = 'All requests';

    protected static ?string $modelLabel = 'Request';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'code';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('organization.slug')->label('Org')->badge()->color('info'),
                TextColumn::make('type')->toggleable(),
                BadgeColumn::make('state')
                    ->getStateUsing(fn (EmployeeRequest $r) => $r->state?->label() ?? '—'),
                TextColumn::make('requester.name')->label('Requester')->placeholder('—'),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllRequests::route('/'),
        ];
    }
}
