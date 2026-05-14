<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HrProcessResource\RelationManagers;

use App\Actions\Hr\ExecuteTask;
use App\Models\HrProcessTask;
use App\Models\States\HrProcessTask\Completed;
use App\Models\States\HrProcessTask\Skipped;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tasks';

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('notes')->rows(2),
            KeyValue::make('result')->keyLabel('Field')->valueLabel('Value'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('order_index')->label('#')->sortable(),
                TextColumn::make('title')->wrap()->limit(60),
                BadgeColumn::make('type'),
                BadgeColumn::make('state')->getStateUsing(fn (HrProcessTask $t) => $t->state?->label() ?? '—'),
                TextColumn::make('due_date')->date()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('—')->toggleable(),
            ])
            ->actions([
                Action::make('execute')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (HrProcessTask $t) => ! ($t->state instanceof Completed || $t->state instanceof Skipped))
                    ->form([
                        KeyValue::make('runtime_data')
                            ->label('Runtime data')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('e.g. asset_id, license_id, note. Required keys depend on task type.'),
                    ])
                    ->action(function (HrProcessTask $t, array $data) {
                        app(ExecuteTask::class)($t, (array) ($data['runtime_data'] ?? []));
                    }),
                Action::make('skip')
                    ->icon('heroicon-o-forward')
                    ->color('gray')
                    ->visible(fn (HrProcessTask $t) => ! ($t->state instanceof Completed || $t->state instanceof Skipped))
                    ->requiresConfirmation()
                    ->action(function (HrProcessTask $t) {
                        $t->state->transitionTo(Skipped::class);
                    }),
            ])
            ->defaultSort('order_index');
    }
}
