<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\MyRequestResource\Pages;
use App\Models\AssetCategory;
use App\Models\EmployeeRequest;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Self-service surface for the employee's own equipment / access requests.
 * Creation is allowed — the CreateMyRequest page wires the SubmitRequest
 * action and stamps the requester from the authenticated employee.
 */
class MyRequestResource extends Resource
{
    protected static ?string $model = EmployeeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'My requests';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        $employee = auth()->user()?->employee;

        return parent::getEloquentQuery()
            ->when($employee, fn ($q) => $q->where('requester_employee_id', $employee->id))
            ->when(! $employee, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('New request')
                ->description('Submit a request for new equipment, accessories, software or an upgrade. Your manager will review first.')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->label('Request type')
                        ->options([
                            EmployeeRequest::TYPE_NEW_ASSET => 'New asset',
                            EmployeeRequest::TYPE_NEW_ACCESSORY => 'New accessory',
                            EmployeeRequest::TYPE_UPGRADE_EXISTING => 'Upgrade existing',
                            EmployeeRequest::TYPE_NEW_LICENSE => 'New software license',
                            EmployeeRequest::TYPE_OTHER => 'Other',
                        ])
                        ->default(EmployeeRequest::TYPE_NEW_ASSET)
                        ->required()
                        ->native(false),
                    Select::make('urgency')
                        ->options([
                            EmployeeRequest::URGENCY_LOW => 'Low',
                            EmployeeRequest::URGENCY_NORMAL => 'Normal',
                            EmployeeRequest::URGENCY_HIGH => 'High',
                            EmployeeRequest::URGENCY_URGENT => 'Urgent',
                        ])
                        ->default(EmployeeRequest::URGENCY_NORMAL)
                        ->required()
                        ->native(false),
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->placeholder('e.g. New laptop for development work'),
                    Select::make('category_id')
                        ->label('Category (optional)')
                        ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->searchable()
                        ->nullable(),
                    TextInput::make('license_name')
                        ->label('Software name')
                        ->maxLength(255)
                        ->visible(fn (callable $get) => $get('type') === EmployeeRequest::TYPE_NEW_LICENSE),
                    Textarea::make('description')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->placeholder('Describe what you need and why.'),
                    Textarea::make('justification')
                        ->label('Business justification (optional)')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('title')->limit(40)->searchable(),
                BadgeColumn::make('type')->formatStateUsing(fn ($s) => str_replace('_', ' ', (string) $s)),
                BadgeColumn::make('urgency')->colors([
                    'gray' => EmployeeRequest::URGENCY_LOW,
                    'primary' => EmployeeRequest::URGENCY_NORMAL,
                    'warning' => EmployeeRequest::URGENCY_HIGH,
                    'danger' => EmployeeRequest::URGENCY_URGENT,
                ]),
                BadgeColumn::make('state')->getStateUsing(fn (EmployeeRequest $r) => $r->state?->label() ?? '—'),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyRequests::route('/'),
            'create' => Pages\CreateMyRequest::route('/create'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->employee !== null;
    }
}
