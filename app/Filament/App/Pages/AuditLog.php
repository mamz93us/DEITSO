<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only audit log viewer scoped to the current organization. Surfaces every
 * activity-log row written by models that use LogsActivity, with filters for
 * subject type, causer, and event. (Sprint 15 — Activity & audit.)
 */
class AuditLog extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Audit log';

    protected static string $view = 'filament.app.pages.audit-log';

    public function getTitle(): string
    {
        return 'Activity & audit log';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->since(),
                TextColumn::make('log_name')->label('Log')->badge()->toggleable(),
                TextColumn::make('event')->badge()->color(fn (?string $state) => match ($state) {
                    'created' => 'success',
                    'updated' => 'warning',
                    'deleted' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->toggleable(),
                TextColumn::make('subject_id')->label('Subject ID')->limit(12)->toggleable(),
                TextColumn::make('causer.name')->label('Causer')->placeholder('System')->toggleable(),
                TextColumn::make('description')->wrap()->limit(80),
            ])
            ->filters([
                SelectFilter::make('event')->options([
                    'created' => 'Created',
                    'updated' => 'Updated',
                    'deleted' => 'Deleted',
                ]),
                SelectFilter::make('log_name')->options(fn () => $this->getTableQuery()
                    ->select('log_name')
                    ->distinct()
                    ->pluck('log_name', 'log_name')
                    ->filter()
                    ->toArray()),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $query = Activity::query()->latest('id');

        // Scope to current org by looking at subject's organization_id where present.
        $org = app()->bound('current.organization') ? app('current.organization') : null;
        if ($org) {
            $query->where(function (Builder $q) use ($org) {
                $q->whereJsonContains('properties->organization_id', $org->id)
                    ->orWhereJsonContains('properties->attributes->organization_id', $org->id);
            });
        }

        return $query;
    }
}
