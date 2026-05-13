<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Actions\Employees\TerminateEmployee;
use App\Filament\App\Resources\EmployeeResource\Pages;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Reports\GenerateEmployeeAssetSummaryPdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('photo')
                        ->collection('photo')
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->columnSpan(1),

                    TextInput::make('code')
                        ->label('Employee code')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated on save (EMP-0001)')
                        ->columnSpan(1),

                    TextInput::make('first_name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('last_name')
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('position')
                        ->placeholder('e.g. Senior IT Technician')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('Login & contact')
                ->columns(2)
                ->schema([
                    TextInput::make('email')
                        ->label('Login email')
                        ->email()
                        ->required(fn (?Employee $record) => $record === null)
                        ->disabled(fn (?Employee $record) => $record !== null)
                        ->dehydrated(fn (?Employee $record) => $record === null)
                        ->unique(table: 'users', column: 'email', ignoreRecord: false)
                        ->helperText(fn (?Employee $record) => $record
                            ? 'Login email is managed on the user account; cannot be edited here.'
                            : 'Used to log into the platform. An initial password is generated.')
                        ->columnSpan(1),

                    TextInput::make('personal_email')
                        ->email()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(32)
                        ->columnSpan(1),

                    TextInput::make('mobile')
                        ->tel()
                        ->maxLength(32)
                        ->columnSpan(1),
                ]),

            Section::make('Org placement')
                ->columns(2)
                ->schema([
                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('department_id')
                        ->label('Department')
                        ->options(fn () => Department::query()->get()->mapWithKeys(fn ($d) => [$d->id => $d->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('manager_employee_id')
                        ->label('Reports to')
                        ->options(fn ($livewire) => Employee::query()
                            ->where('status', '!=', Employee::STATUS_TERMINATED)
                            ->when($livewire->record ?? null, fn ($q, $rec) => $q->whereKeyNot($rec))
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                        ->searchable()
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Employment')
                ->columns(3)
                ->schema([
                    DatePicker::make('hire_date')
                        ->default(now())
                        ->columnSpan(1),

                    DatePicker::make('termination_date')
                        ->columnSpan(1),

                    Select::make('status')
                        ->options([
                            Employee::STATUS_ACTIVE => 'Active',
                            Employee::STATUS_ON_LEAVE => 'On leave',
                            Employee::STATUS_TERMINATED => 'Terminated',
                        ])
                        ->default(Employee::STATUS_ACTIVE)
                        ->required()
                        ->columnSpan(1),
                ]),

            Section::make('Custom fields')
                ->collapsed()
                ->schema([
                    KeyValue::make('custom_fields')
                        ->keyLabel('Field')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->collection('photo')
                    ->circular(),
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('first_name')->label('Name')
                    ->formatStateUsing(fn (Employee $r) => $r->full_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('position')->toggleable()->searchable(),
                TextColumn::make('department.name')->label('Dept')->toggleable(),
                TextColumn::make('branch.name')->label('Branch')->toggleable(),
                TextColumn::make('manager.first_name')->label('Manager')->placeholder('—')->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => Employee::STATUS_ACTIVE,
                        'warning' => Employee::STATUS_ON_LEAVE,
                        'danger' => Employee::STATUS_TERMINATED,
                    ]),
                TextColumn::make('hire_date')->date()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Employee::STATUS_ACTIVE => 'Active',
                        Employee::STATUS_ON_LEAVE => 'On leave',
                        Employee::STATUS_TERMINATED => 'Terminated',
                    ]),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name])),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(fn () => Department::query()->get()->mapWithKeys(fn ($d) => [$d->id => $d->name])),
            ])
            ->actions([
                EditAction::make(),
                Action::make('asset_summary')
                    ->label('Asset summary PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function (Employee $e) {
                        $pdf = app(GenerateEmployeeAssetSummaryPdf::class)($e);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            'asset-summary-'.$e->code.'.pdf',
                            ['Content-Type' => 'application/pdf'],
                        );
                    }),
                Action::make('terminate')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Employee $e) => $e->status !== Employee::STATUS_TERMINATED)
                    ->requiresConfirmation()
                    ->modalHeading('Terminate employee')
                    ->modalDescription('Sets status to Terminated and disables the user account. Historical records are preserved.')
                    ->form([
                        DatePicker::make('termination_date')->default(now())->required(),
                        TextInput::make('reason')->placeholder('Optional note')->maxLength(255),
                    ])
                    ->action(function (Employee $e, array $data) {
                        app(TerminateEmployee::class)(
                            $e,
                            $data['termination_date'] ? Carbon::parse($data['termination_date']) : null,
                            $data['reason'] ?? null,
                        );
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('code', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
