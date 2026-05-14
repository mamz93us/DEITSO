<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ContractResource\Pages;
use App\Models\Contract;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
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

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Costing';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Contract')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(32)
                        ->helperText('Unique within the organization.'),
                    Select::make('status')
                        ->options([
                            Contract::STATUS_ACTIVE => 'Active',
                            Contract::STATUS_EXPIRED => 'Expired',
                            Contract::STATUS_CANCELLED => 'Cancelled',
                        ])
                        ->default(Contract::STATUS_ACTIVE)
                        ->required(),
                    TextInput::make('name.en')->label('Name (EN)')->required()->maxLength(255),
                    TextInput::make('name.ar')->label('Name (AR)')->maxLength(255),
                    Select::make('customer_organization_id')
                        ->label('Customer organization (optional)')
                        ->options(fn () => Organization::query()->withoutGlobalScopes()->pluck('slug', 'id'))
                        ->searchable()
                        ->nullable(),
                    TextInput::make('included_hours_per_month')
                        ->label('Included hours per month')
                        ->numeric()
                        ->step('0.25')
                        ->default(0)
                        ->required(),
                    DatePicker::make('start_date')->native(false)->required(),
                    DatePicker::make('end_date')->native(false)->nullable(),
                    Toggle::make('auto_renew')->default(false)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->copyable(),
                TextColumn::make('name')
                    ->searchable()
                    ->formatStateUsing(fn ($s, Contract $c) => $c->getTranslation('name', 'en')),
                TextColumn::make('included_hours_per_month')->label('Hours/mo')->numeric(2)->toggleable(),
                TextColumn::make('start_date')->date()->toggleable(),
                TextColumn::make('end_date')->date()->toggleable(),
                BadgeColumn::make('status')->colors([
                    'success' => Contract::STATUS_ACTIVE,
                    'gray' => Contract::STATUS_EXPIRED,
                    'danger' => Contract::STATUS_CANCELLED,
                ]),
                IconColumn::make('auto_renew')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Contract::STATUS_ACTIVE => 'Active',
                    Contract::STATUS_EXPIRED => 'Expired',
                    Contract::STATUS_CANCELLED => 'Cancelled',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
