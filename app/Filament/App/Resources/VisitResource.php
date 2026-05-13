<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Actions\Visits\CheckInVisit;
use App\Actions\Visits\CloseVisit;
use App\Filament\App\Resources\VisitResource\Pages;
use App\Models\Branch;
use App\Models\States\Visit\Cancelled;
use App\Models\States\Visit\Completed;
use App\Models\States\Visit\InProgress;
use App\Models\States\Visit\Scheduled;
use App\Models\User;
use App\Models\Visit;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Visits';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Visit')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options([
                            Visit::TYPE_ONLINE => 'Online',
                            Visit::TYPE_OFFLINE => 'On-site',
                        ])
                        ->default(Visit::TYPE_OFFLINE)
                        ->required()
                        ->native(false),
                    DateTimePicker::make('scheduled_at')->required(),
                    Select::make('technician_user_id')
                        ->label('Technician')
                        ->options(fn () => User::query()->orderBy('name')->limit(500)->pluck('name', 'id'))
                        ->searchable(),
                    Select::make('customer_branch_id')
                        ->label('Customer site')
                        ->options(fn () => Branch::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->name]))
                        ->searchable()
                        ->nullable(),
                    Textarea::make('summary')->rows(3)->columnSpanFull(),
                    Textarea::make('internal_notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable()->sortable(),
                BadgeColumn::make('type'),
                BadgeColumn::make('state')->getStateUsing(fn (Visit $v) => $v->state?->label() ?? '—'),
                TextColumn::make('technician.name')->label('Tech')->toggleable(),
                TextColumn::make('customerBranch.name')->label('Site')->toggleable(),
                TextColumn::make('scheduled_at')->dateTime()->sortable(),
                TextColumn::make('duration_minutes')->label('Mins')->toggleable(),
                IconColumn::make('is_billable')->boolean()->toggleable(),
                TextColumn::make('total_charge_minor')
                    ->money(fn (Visit $v) => $v->currency ?: 'EGP', divideBy: 100)
                    ->label('Charge')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    Visit::TYPE_ONLINE => 'Online',
                    Visit::TYPE_OFFLINE => 'On-site',
                ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('check_in')
                    ->icon('heroicon-o-map-pin')
                    ->color('primary')
                    ->visible(fn (Visit $v) => $v->state instanceof Scheduled)
                    ->form([
                        TextInput::make('lat')->label('Latitude')->numeric()->step('0.0000001'),
                        TextInput::make('lng')->label('Longitude')->numeric()->step('0.0000001'),
                    ])
                    ->action(function (Visit $v, array $data) {
                        app(CheckInVisit::class)(
                            $v,
                            $data['lat'] !== null && $data['lat'] !== '' ? (float) $data['lat'] : null,
                            $data['lng'] !== null && $data['lng'] !== '' ? (float) $data['lng'] : null,
                        );
                    }),
                Action::make('close')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Visit $v) => $v->state instanceof InProgress)
                    ->form([
                        TextInput::make('lat')->numeric()->step('0.0000001'),
                        TextInput::make('lng')->numeric()->step('0.0000001'),
                        Textarea::make('summary')->rows(3),
                    ])
                    ->action(function (Visit $v, array $data) {
                        app(CloseVisit::class)(
                            $v,
                            $data['lat'] !== null && $data['lat'] !== '' ? (float) $data['lat'] : null,
                            $data['lng'] !== null && $data['lng'] !== '' ? (float) $data['lng'] : null,
                            $data['summary'] ?? null,
                        );
                    }),
                Action::make('cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Visit $v) => ! ($v->state instanceof Completed || $v->state instanceof Cancelled))
                    ->requiresConfirmation()
                    ->action(function (Visit $v) {
                        $v->state->transitionTo(Cancelled::class);
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}
