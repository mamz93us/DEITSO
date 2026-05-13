<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyTicketResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Comments visible to the end user. Internal staff notes are filtered out.
 */
class PublicCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Conversation';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $q) => $q->where('is_internal', false))
            ->columns([
                TextColumn::make('user.name')->label('From'),
                BadgeColumn::make('author_type'),
                TextColumn::make('body')->wrap(),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
