<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmailAccountResource\Pages;
use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Models\Employee;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailAccountResource extends Resource
{
    protected static ?string $model = EmailAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?string $navigationGroup = 'Email';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'full_address';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Mailbox')
                ->columns(2)
                ->schema([
                    Select::make('email_domain_id')
                        ->label('Domain')
                        ->options(fn () => EmailDomain::query()->where('is_active', true)->pluck('domain', 'id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('local_part')
                        ->label('Username (before @)')
                        ->required()
                        ->maxLength(64),
                    TextInput::make('quota_mb')
                        ->label('Quota (MB)')
                        ->numeric()
                        ->placeholder('Leave blank for unlimited'),
                    Select::make('assigned_employee_id')
                        ->label('Assign to employee')
                        ->options(fn () => Employee::query()->get()->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                        ->searchable()
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_address')->searchable()->copyable(),
                TextColumn::make('emailDomain.domain')->label('Domain')->toggleable(),
                TextColumn::make('quota_mb')->label('Quota MB')->placeholder('∞')->toggleable(),
                TextColumn::make('assignedEmployee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($s, EmailAccount $a) => $a->assignedEmployee?->full_name ?? '—')
                    ->toggleable(),
                BadgeColumn::make('status')->colors([
                    'success' => EmailAccount::STATUS_ACTIVE,
                    'warning' => EmailAccount::STATUS_SUSPENDED,
                    'danger' => EmailAccount::STATUS_DELETED,
                ]),
                TextColumn::make('last_sync_at')->dateTime()->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    EmailAccount::STATUS_ACTIVE => 'Active',
                    EmailAccount::STATUS_SUSPENDED => 'Suspended',
                    EmailAccount::STATUS_DELETED => 'Deleted',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailAccounts::route('/'),
            'create' => Pages\CreateEmailAccount::route('/create'),
            'edit' => Pages\EditEmailAccount::route('/{record}/edit'),
        ];
    }
}
