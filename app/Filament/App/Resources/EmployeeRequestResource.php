<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Actions\Requests\ApproveByAdmin;
use App\Actions\Requests\ApproveByManager;
use App\Actions\Requests\CancelRequest;
use App\Actions\Requests\FulfillFromStock;
use App\Actions\Requests\RejectRequest;
use App\Filament\App\Resources\EmployeeRequestResource\Pages;
use App\Filament\App\Resources\EmployeeRequestResource\RelationManagers\CommentsRelationManager;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminApproved;
use App\Models\States\EmployeeRequest\Cancelled;
use App\Models\States\EmployeeRequest\Fulfilled;
use App\Models\States\EmployeeRequest\InProcurement;
use App\Models\States\EmployeeRequest\ManagerApproved;
use App\Models\States\EmployeeRequest\Submitted;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class EmployeeRequestResource extends Resource
{
    protected static ?string $model = EmployeeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Requests';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Request')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options([
                            EmployeeRequest::TYPE_NEW_ASSET => 'New asset',
                            EmployeeRequest::TYPE_NEW_ACCESSORY => 'New accessory',
                            EmployeeRequest::TYPE_UPGRADE_EXISTING => 'Upgrade existing',
                            EmployeeRequest::TYPE_NEW_LICENSE => 'New license',
                            EmployeeRequest::TYPE_OTHER => 'Other',
                        ])
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

                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(4)->columnSpanFull(),
                    Textarea::make('justification')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Target')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Select::make('requester_employee_id')
                        ->label('Requester')
                        ->options(fn () => Employee::query()
                            ->where('status', '!=', Employee::STATUS_TERMINATED)
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                        ->required()
                        ->searchable(),

                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                        ->searchable()
                        ->nullable(),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->searchable()
                        ->nullable(),

                    Select::make('asset_model_id')
                        ->label('Suggested model')
                        ->options(fn () => AssetModel::query()->get()->mapWithKeys(fn ($m) => [$m->id => trim($m->manufacturer.' '.$m->model_name)]))
                        ->searchable()
                        ->nullable(),

                    TextInput::make('estimated_cost_minor')
                        ->label('Estimated cost (minor units)')
                        ->numeric()
                        ->minValue(0),

                    TextInput::make('currency')->default('EGP')->maxLength(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('title')->limit(40)->searchable(),
                BadgeColumn::make('type')->formatStateUsing(fn ($s) => str_replace('_', ' ', $s)),
                TextColumn::make('requester.first_name')
                    ->label('Requester')
                    ->formatStateUsing(fn ($s, EmployeeRequest $r) => $r->requester?->full_name ?? '—'),
                BadgeColumn::make('urgency')->colors([
                    'gray' => EmployeeRequest::URGENCY_LOW,
                    'primary' => EmployeeRequest::URGENCY_NORMAL,
                    'warning' => EmployeeRequest::URGENCY_HIGH,
                    'danger' => EmployeeRequest::URGENCY_URGENT,
                ]),
                BadgeColumn::make('state')
                    ->getStateUsing(fn (EmployeeRequest $r) => $r->state?->label() ?? '—')
                    ->colors([
                        'gray' => ['Draft', 'Cancelled'],
                        'info' => ['Submitted', 'In procurement'],
                        'warning' => 'Manager approved',
                        'primary' => 'Admin approved',
                        'success' => 'Fulfilled',
                        'danger' => ['Manager rejected', 'Admin rejected'],
                    ]),
                TextColumn::make('estimated_cost_minor')
                    ->money(fn (EmployeeRequest $r) => $r->currency ?: 'EGP', divideBy: 100)
                    ->label('Est. cost')
                    ->toggleable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    EmployeeRequest::TYPE_NEW_ASSET => 'New asset',
                    EmployeeRequest::TYPE_NEW_ACCESSORY => 'New accessory',
                    EmployeeRequest::TYPE_UPGRADE_EXISTING => 'Upgrade existing',
                    EmployeeRequest::TYPE_NEW_LICENSE => 'New license',
                    EmployeeRequest::TYPE_OTHER => 'Other',
                ]),
                SelectFilter::make('urgency')->options([
                    EmployeeRequest::URGENCY_LOW => 'Low',
                    EmployeeRequest::URGENCY_NORMAL => 'Normal',
                    EmployeeRequest::URGENCY_HIGH => 'High',
                    EmployeeRequest::URGENCY_URGENT => 'Urgent',
                ]),
            ])
            ->actions([
                EditAction::make(),

                Action::make('approve_manager')
                    ->label('Approve (manager)')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->visible(fn (EmployeeRequest $r) => $r->state instanceof Submitted)
                    ->form([Textarea::make('notes')->rows(2)])
                    ->action(function (EmployeeRequest $r, array $data) {
                        app(ApproveByManager::class)($r, auth()->user(), $data['notes'] ?? null);
                    }),

                Action::make('approve_admin')
                    ->label('Approve (admin)')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (EmployeeRequest $r) => $r->state instanceof Submitted || $r->state instanceof ManagerApproved)
                    ->form([Textarea::make('notes')->rows(2)])
                    ->action(function (EmployeeRequest $r, array $data) {
                        app(ApproveByAdmin::class)($r, auth()->user(), $data['notes'] ?? null);
                    }),

                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (EmployeeRequest $r) => $r->state instanceof Submitted || $r->state instanceof ManagerApproved)
                    ->form([
                        Select::make('rejecter_role')
                            ->options(['manager' => 'Manager', 'admin' => 'Admin'])
                            ->default(fn (EmployeeRequest $r) => $r->state instanceof ManagerApproved ? 'admin' : 'manager')
                            ->required(),
                        Textarea::make('notes')->rows(2),
                    ])
                    ->action(function (EmployeeRequest $r, array $data) {
                        app(RejectRequest::class)(
                            $r,
                            auth()->user(),
                            $data['rejecter_role'],
                            $data['notes'] ?? null,
                        );
                    }),

                Action::make('fulfill_from_stock')
                    ->label('Fulfill from stock')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->visible(fn (EmployeeRequest $r) => $r->state instanceof AdminApproved)
                    ->form([
                        Select::make('asset_id')
                            ->label('Asset')
                            ->options(fn (EmployeeRequest $r) => Asset::query()
                                ->when($r->category_id, fn ($q) => $q->where('category_id', $r->category_id))
                                ->where('status', Asset::STATUS_IN_STOCK)
                                ->get()
                                ->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.($a->name ?: $a->assetModel?->model_name ?? '')]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (EmployeeRequest $r, array $data) {
                        $asset = Asset::findOrFail($data['asset_id']);
                        app(FulfillFromStock::class)($r, $asset);
                    }),

                Action::make('cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (EmployeeRequest $r) => ! ($r->state instanceof InProcurement
                        || $r->state instanceof Fulfilled
                        || $r->state instanceof Cancelled))
                    ->requiresConfirmation()
                    ->action(fn (EmployeeRequest $r) => app(CancelRequest::class)($r)),

                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeRequests::route('/'),
            'create' => Pages\CreateEmployeeRequest::route('/create'),
            'edit' => Pages\EditEmployeeRequest::route('/{record}/edit'),
        ];
    }
}
