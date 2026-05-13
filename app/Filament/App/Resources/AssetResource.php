<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\AssignLicenseSeat;
use App\Actions\Assets\RevokeAssetFromEmployee;
use App\Actions\Assets\ScrapAsset;
use App\Actions\Assets\TransferAsset;
use App\Filament\App\Resources\AssetResource\Pages;
use App\Filament\App\Resources\AssetResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\App\Resources\AssetResource\RelationManagers\LicenseSeatsRelationManager;
use App\Filament\App\Resources\AssetResource\RelationManagers\RemoteAccessRelationManager;
use App\Filament\App\Resources\AssetResource\RelationManagers\TransfersRelationManager;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\States\AssetTransfer\Approved;
use App\Models\States\AssetTransfer\Pending;
use App\Models\Supplier;
use App\Services\Labels\GenerateAssetLabelPdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'ITAM';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Asset code')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated on save (e.g. SMR-CAI-LAP-0001)')
                        ->columnSpan(1),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->required()
                        ->searchable()
                        ->live()
                        ->columnSpan(1),

                    Select::make('asset_model_id')
                        ->label('Model (catalog)')
                        ->options(fn (callable $get) => AssetModel::query()
                            ->when($get('category_id'), fn ($q, $id) => $q->where('category_id', $id))
                            ->get()
                            ->mapWithKeys(fn ($m) => [$m->id => trim($m->manufacturer.' '.$m->model_name)]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    TextInput::make('name')
                        ->label('Display name (override)')
                        ->placeholder('Leave empty to use the model name')
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('serial_number')
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->columnSpan(1),
                ]),

            Section::make('Status & assignment')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->options([
                            Asset::STATUS_IN_STOCK => 'In stock',
                            Asset::STATUS_DEPLOYED => 'Deployed',
                            Asset::STATUS_IN_MAINTENANCE => 'In maintenance',
                            Asset::STATUS_RETIRED => 'Retired',
                            Asset::STATUS_SCRAPPED => 'Scrapped',
                            Asset::STATUS_LOST => 'Lost',
                        ])
                        ->default(Asset::STATUS_IN_STOCK)
                        ->required()
                        ->columnSpan(1),

                    Select::make('branch_id')
                        ->label('Home branch')
                        ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('assigned_employee_id')
                        ->label('Assigned to (employee)')
                        ->helperText('For deployed assets. Full assignment history comes in Sprint 4.')
                        ->options(fn () => Employee::query()
                            ->where('status', '!=', Employee::STATUS_TERMINATED)
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('assigned_branch_id')
                        ->label('Assigned to (branch)')
                        ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),
                ]),

            Section::make('License details')
                ->columns(2)
                ->visible(function (callable $get) {
                    $categoryId = $get('category_id');
                    if (! $categoryId) {
                        return false;
                    }
                    $cat = AssetCategory::find($categoryId);

                    return $cat?->tracking_mode === AssetCategory::TRACKING_LICENSE;
                })
                ->schema([
                    TextInput::make('license_key_encrypted')
                        ->label('License key')
                        ->password()
                        ->revealable()
                        ->helperText('Stored encrypted at rest.')
                        ->columnSpanFull(),

                    TextInput::make('seats_total')
                        ->label('Total seats')
                        ->numeric()
                        ->minValue(1)
                        ->columnSpan(1),

                    DatePicker::make('expiry_date')
                        ->label('Expiry date')
                        ->columnSpan(1),

                    Toggle::make('renewable')
                        ->label('Renewable')
                        ->inline(false)
                        ->columnSpan(1),

                    Toggle::make('auto_renewal')
                        ->label('Auto-renews')
                        ->inline(false)
                        ->columnSpan(1),
                ]),

            Section::make('Procurement & cost')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Select::make('supplier_id')
                        ->options(fn () => Supplier::query()->get()->mapWithKeys(fn ($s) => [$s->id => $s->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),

                    TextInput::make('purchase_order_reference')->maxLength(255)->columnSpan(1),
                    DatePicker::make('purchase_date')->columnSpan(1),
                    TextInput::make('purchase_cost_minor')
                        ->label('Purchase cost (minor units)')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    TextInput::make('currency')->default('EGP')->maxLength(3)->columnSpan(1),
                    DatePicker::make('warranty_until')->columnSpan(1),
                    TextInput::make('vendor')->maxLength(255)->columnSpan(1),
                ]),

            Section::make('Custom fields & notes')
                ->collapsed()
                ->schema([
                    KeyValue::make('custom_fields')->columnSpanFull(),
                    Textarea::make('notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                TextColumn::make('name')
                    ->formatStateUsing(fn (Asset $a) => $a->name ?: ($a->assetModel?->model_name ?? '—'))
                    ->label('Name'),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('serial_number')->toggleable()->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => Asset::STATUS_IN_STOCK,
                        'primary' => Asset::STATUS_DEPLOYED,
                        'warning' => [Asset::STATUS_IN_MAINTENANCE, Asset::STATUS_RETIRED],
                        'danger' => [Asset::STATUS_SCRAPPED, Asset::STATUS_LOST],
                    ]),
                TextColumn::make('assignedEmployee.first_name')->label('Holder')
                    ->formatStateUsing(fn ($state, Asset $a) => $a->assignedEmployee?->full_name ?? '—')
                    ->toggleable(),
                TextColumn::make('branch.name')->label('Branch')->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->toggleable(),
                TextColumn::make('warranty_until')->date()->toggleable(),
                TextColumn::make('expiry_date')
                    ->date()
                    ->label('License expiry')
                    ->color(fn (Asset $a) => $a->is_expired ? 'danger' : ($a->is_expiring_soon ? 'warning' : null))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('seats_used')
                    ->label('Seats')
                    ->state(fn (Asset $a) => $a->is_license ? $a->seats_used.' / '.($a->seats_total ?? '∞') : '—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Asset::STATUS_IN_STOCK => 'In stock',
                    Asset::STATUS_DEPLOYED => 'Deployed',
                    Asset::STATUS_IN_MAINTENANCE => 'In maintenance',
                    Asset::STATUS_RETIRED => 'Retired',
                    Asset::STATUS_SCRAPPED => 'Scrapped',
                    Asset::STATUS_LOST => 'Lost',
                ]),
                SelectFilter::make('category_id')->label('Category')
                    ->options(fn () => AssetCategory::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name])),
                SelectFilter::make('branch_id')->label('Branch')
                    ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name])),
            ])
            ->actions([
                EditAction::make(),

                Action::make('assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (Asset $a) => ! $a->is_license
                        && ! in_array($a->status, [Asset::STATUS_SCRAPPED, Asset::STATUS_LOST], true))
                    ->form([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(fn () => Employee::query()
                                ->where('status', '!=', Employee::STATUS_TERMINATED)
                                ->get()
                                ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                            ->required()
                            ->searchable(),
                        TextInput::make('reason')->placeholder('Optional note'),
                    ])
                    ->action(function (Asset $asset, array $data) {
                        app(AssignAssetToEmployee::class)(
                            $asset,
                            Employee::findOrFail($data['employee_id']),
                            $data['reason'] ?? null,
                        );
                    }),

                Action::make('revoke')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Asset $a) => $a->assigned_employee_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Return asset to stock')
                    ->form([
                        TextInput::make('reason')->placeholder('Optional reason'),
                    ])
                    ->action(function (Asset $asset, array $data) {
                        app(RevokeAssetFromEmployee::class)($asset, $data['reason'] ?? null);
                    }),

                Action::make('transfer')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (Asset $a) => ! $a->is_license
                        && ! in_array($a->status, [Asset::STATUS_SCRAPPED, Asset::STATUS_LOST], true))
                    ->form([
                        Select::make('to_employee_id')
                            ->label('Transfer to employee')
                            ->options(fn () => Employee::query()
                                ->where('status', '!=', Employee::STATUS_TERMINATED)
                                ->get()
                                ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                            ->searchable()
                            ->nullable(),
                        Select::make('to_branch_id')
                            ->label('…or transfer to branch')
                            ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                            ->searchable()
                            ->nullable(),
                        Textarea::make('reason')->rows(2),
                    ])
                    ->action(function (Asset $asset, array $data) {
                        $svc = app(TransferAsset::class);
                        $transfer = $svc->create($asset, $data);
                        // Auto-approve + complete in this simple flow — full
                        // approval workflow lands when EmployeeRequest ties in (Sprint 7).
                        $transfer->state->transitionTo(Pending::class);
                        $transfer->state->transitionTo(Approved::class);
                        $svc->complete($transfer);
                    }),

                Action::make('assign_seat')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->label('Assign seat')
                    ->visible(fn (Asset $a) => $a->is_license && ! $a->is_expired)
                    ->form([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(fn () => Employee::query()
                                ->where('status', '!=', Employee::STATUS_TERMINATED)
                                ->get()
                                ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                            ->required()
                            ->searchable(),
                        TextInput::make('reason')->placeholder('Optional note'),
                    ])
                    ->action(function (Asset $asset, array $data) {
                        app(AssignLicenseSeat::class)(
                            $asset,
                            Employee::findOrFail($data['employee_id']),
                            $data['reason'] ?? null,
                        );
                    }),

                Action::make('print_label')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(function (Asset $asset) {
                        $pdf = app(GenerateAssetLabelPdf::class)->single($asset);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            'label-'.$asset->code.'.pdf',
                            ['Content-Type' => 'application/pdf'],
                        );
                    }),

                Action::make('scrap')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Asset $a) => $a->status !== Asset::STATUS_SCRAPPED)
                    ->requiresConfirmation()
                    ->modalHeading('Scrap asset')
                    ->modalDescription('Removes the asset from active use. Historical data is preserved.')
                    ->form([
                        Select::make('reason')
                            ->options([
                                'end_of_life' => 'End of life',
                                'damaged' => 'Damaged',
                                'lost' => 'Lost',
                                'sold' => 'Sold',
                                'donated' => 'Donated',
                                'other' => 'Other',
                            ])
                            ->default('end_of_life')
                            ->required(),
                        TextInput::make('disposal_method')->placeholder('e.g. recycled via vendor'),
                        TextInput::make('residual_value_minor')
                            ->label('Residual value (minor units)')
                            ->numeric()
                            ->minValue(0),
                        Textarea::make('notes')->rows(2),
                    ])
                    ->action(function (Asset $asset, array $data) {
                        app(ScrapAsset::class)($asset, $data);
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('print_labels')
                        ->label('Print labels (A4 sheet)')
                        ->icon('heroicon-o-printer')
                        ->action(function ($records) {
                            $pdf = app(GenerateAssetLabelPdf::class)->sheet(collect($records));

                            return response()->streamDownload(
                                fn () => print ($pdf->output()),
                                'asset-labels-'.now()->format('Y-m-d-Hi').'.pdf',
                                ['Content-Type' => 'application/pdf'],
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LicenseSeatsRelationManager::class,
            AssignmentsRelationManager::class,
            RemoteAccessRelationManager::class,
            TransfersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
