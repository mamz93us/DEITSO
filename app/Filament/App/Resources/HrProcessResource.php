<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Actions\Hr\CompleteProcess;
use App\Filament\App\Resources\HrProcessResource\Pages;
use App\Filament\App\Resources\HrProcessResource\RelationManagers\TasksRelationManager;
use App\Models\Employee;
use App\Models\HrProcess;
use App\Models\HrWorkflowTemplate;
use App\Models\States\HrProcess\Cancelled;
use App\Models\States\HrProcess\Completed;
use App\Models\States\HrProcess\InProgress;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrProcessResource extends Resource
{
    protected static ?string $model = HrProcess::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'HR';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Processes';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Process')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options([
                            HrProcess::TYPE_ONBOARDING => 'Onboarding',
                            HrProcess::TYPE_OFFBOARDING => 'Offboarding',
                        ])
                        ->required()
                        ->native(false),
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(fn () => Employee::query()
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                        ->searchable()
                        ->required(),
                    Select::make('template_id')
                        ->label('Template')
                        ->options(fn () => HrWorkflowTemplate::query()
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => $t->name.' ('.$t->type.')']))
                        ->searchable()
                        ->nullable(),
                    DatePicker::make('target_date')->label('Start / last day'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                BadgeColumn::make('type')->colors([
                    'primary' => HrProcess::TYPE_ONBOARDING,
                    'danger' => HrProcess::TYPE_OFFBOARDING,
                ]),
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($s, HrProcess $p) => $p->employee?->full_name ?? '—'),
                BadgeColumn::make('state')->getStateUsing(fn (HrProcess $p) => $p->state?->label() ?? '—'),
                TextColumn::make('target_date')->date()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
                TextColumn::make('tasks_count')->counts('tasks')->label('Tasks'),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    HrProcess::TYPE_ONBOARDING => 'Onboarding',
                    HrProcess::TYPE_OFFBOARDING => 'Offboarding',
                ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (HrProcess $p) => $p->state instanceof InProgress)
                    ->requiresConfirmation()
                    ->modalDescription('Confirm all required tasks are done. Offboarding processes generate a handover PDF.')
                    ->action(function (HrProcess $p) {
                        app(CompleteProcess::class)($p);
                    }),
                Action::make('cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (HrProcess $p) => ! ($p->state instanceof Completed || $p->state instanceof Cancelled))
                    ->requiresConfirmation()
                    ->action(function (HrProcess $p) {
                        $p->state->transitionTo(Cancelled::class);
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrProcesses::route('/'),
            'create' => Pages\CreateHrProcess::route('/create'),
            'edit' => Pages\EditHrProcess::route('/{record}/edit'),
        ];
    }
}
