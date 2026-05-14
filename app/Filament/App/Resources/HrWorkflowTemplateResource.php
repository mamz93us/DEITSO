<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\HrWorkflowTemplateResource\Pages;
use App\Models\Department;
use App\Models\HrWorkflowTemplate;
use App\Models\HrWorkflowTemplateTask;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrWorkflowTemplateResource extends Resource
{
    use Translatable;

    protected static ?string $model = HrWorkflowTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'HR';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Workflow templates';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Template')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpan(1),
                    Select::make('type')
                        ->options([
                            HrWorkflowTemplate::TYPE_ONBOARDING => 'Onboarding',
                            HrWorkflowTemplate::TYPE_OFFBOARDING => 'Offboarding',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpan(1),
                    Select::make('department_id')
                        ->label('Department (optional)')
                        ->options(fn () => Department::query()->get()->mapWithKeys(fn ($d) => [$d->id => $d->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),
                    TextInput::make('position_tag')->placeholder('e.g. senior_engineer')->maxLength(64)->columnSpan(1),
                    Toggle::make('is_default')->inline(false)->columnSpan(1),
                    Toggle::make('is_active')->default(true)->inline(false)->columnSpan(1),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Tasks')
                ->description('Ordered list of tasks. Each task is typed so the right module knows how to execute it on process start.')
                ->schema([
                    Repeater::make('tasks')
                        ->relationship()
                        ->orderColumn('order_index')
                        ->reorderable()
                        ->collapsible()
                        ->columns(2)
                        ->schema([
                            TextInput::make('title')->required()->maxLength(255)->columnSpan(1),
                            Select::make('type')
                                ->options([
                                    HrWorkflowTemplateTask::TYPE_MANUAL => 'Manual',
                                    HrWorkflowTemplateTask::TYPE_ASSIGN_ASSET => 'Assign asset',
                                    HrWorkflowTemplateTask::TYPE_ASSIGN_ACCESSORY => 'Assign accessory',
                                    HrWorkflowTemplateTask::TYPE_ASSIGN_LICENSE => 'Assign license',
                                    HrWorkflowTemplateTask::TYPE_CREATE_EMAIL => 'Create email account',
                                    HrWorkflowTemplateTask::TYPE_GRANT_ACCESS => 'Grant access',
                                    HrWorkflowTemplateTask::TYPE_CUSTOM_ACTION => 'Custom action',
                                    HrWorkflowTemplateTask::TYPE_COLLECT_ASSET => 'Collect asset',
                                    HrWorkflowTemplateTask::TYPE_DELETE_EMAIL => 'Delete email account',
                                    HrWorkflowTemplateTask::TYPE_SUSPEND_EMAIL => 'Suspend email account',
                                    HrWorkflowTemplateTask::TYPE_REVOKE_LICENSE => 'Revoke license',
                                    HrWorkflowTemplateTask::TYPE_DISABLE_USER => 'Disable user',
                                    HrWorkflowTemplateTask::TYPE_DATA_BACKUP => 'Data backup',
                                ])
                                ->default(HrWorkflowTemplateTask::TYPE_MANUAL)
                                ->required()
                                ->native(false)
                                ->columnSpan(1),
                            Select::make('assignee_role')
                                ->options([
                                    HrWorkflowTemplateTask::ROLE_IT_TECHNICIAN => 'IT technician',
                                    HrWorkflowTemplateTask::ROLE_HR => 'HR',
                                    HrWorkflowTemplateTask::ROLE_PROCUREMENT => 'Procurement',
                                    HrWorkflowTemplateTask::ROLE_MANAGER => 'Manager',
                                    HrWorkflowTemplateTask::ROLE_REQUESTER => 'Requester',
                                    HrWorkflowTemplateTask::ROLE_OTHER => 'Other',
                                ])
                                ->default(HrWorkflowTemplateTask::ROLE_IT_TECHNICIAN)
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('due_offset_days')
                                ->label('Due offset (days from target date)')
                                ->numeric()
                                ->default(0)
                                ->columnSpan(1),
                            Toggle::make('is_required')->default(true)->inline(false)->columnSpan(1),
                            Textarea::make('description')->rows(2)->columnSpanFull(),
                            KeyValue::make('config')
                                ->keyLabel('Setting')
                                ->valueLabel('Value')
                                ->helperText('Task-type-specific configuration (e.g. {"asset_category_code":"LAP"}).')
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                BadgeColumn::make('type')->colors([
                    'primary' => HrWorkflowTemplate::TYPE_ONBOARDING,
                    'danger' => HrWorkflowTemplate::TYPE_OFFBOARDING,
                ]),
                TextColumn::make('department.name')->label('Department')->placeholder('All'),
                TextColumn::make('tasks_count')->counts('tasks')->label('Tasks'),
                IconColumn::make('is_default')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    HrWorkflowTemplate::TYPE_ONBOARDING => 'Onboarding',
                    HrWorkflowTemplate::TYPE_OFFBOARDING => 'Offboarding',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrWorkflowTemplates::route('/'),
            'create' => Pages\CreateHrWorkflowTemplate::route('/create'),
            'edit' => Pages\EditHrWorkflowTemplate::route('/{record}/edit'),
        ];
    }
}
