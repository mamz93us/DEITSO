<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetResource\RelationManagers;

use App\Actions\Assets\AssignLicenseSeat;
use App\Actions\Assets\RevokeLicenseSeat;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * License-seat assignments. Shown only when the asset is a license.
 * Each row is one seat. Add → opens AssignLicenseSeat. Revoke → closes the row.
 */
class LicenseSeatsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'License seats';

    protected static ?string $icon = 'heroicon-o-key';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var Asset $ownerRecord */
        return $ownerRecord->tracking_mode === AssetCategory::TRACKING_LICENSE;
    }

    public function form(Form $form): Form
    {
        // Used only by the inline "Add seat" header action.
        return $form->schema([
            Select::make('assigned_to_id')
                ->label('Employee')
                ->options(fn () => Employee::query()
                    ->where('status', '!=', Employee::STATUS_TERMINATED)
                    ->get()
                    ->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')']))
                ->required()
                ->searchable(),
            TextInput::make('reason')->placeholder('Optional note'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('to_at')) // only open seats by default
            ->columns([
                TextColumn::make('holder_name')
                    ->label('Employee')
                    ->state(fn (AssetAssignment $r) => optional(Employee::find($r->assigned_to_id))->full_name ?? '—'),
                TextColumn::make('from_at')->dateTime()->label('Assigned'),
                TextColumn::make('to_at')->dateTime()->placeholder('current')->label('Revoked'),
                IconColumn::make('is_current')
                    ->boolean()
                    ->state(fn (AssetAssignment $r) => $r->to_at === null)
                    ->label('Active'),
                TextColumn::make('reason')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add seat')
                    ->using(function (array $data) {
                        /** @var Asset $owner */
                        $owner = $this->getOwnerRecord();
                        $employee = Employee::findOrFail($data['assigned_to_id']);

                        return app(AssignLicenseSeat::class)(
                            $owner,
                            $employee,
                            $data['reason'] ?? null,
                        );
                    }),
            ])
            ->actions([
                Action::make('revoke_seat')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->visible(fn (AssetAssignment $r) => $r->to_at === null)
                    ->requiresConfirmation()
                    ->action(function (AssetAssignment $row) {
                        app(RevokeLicenseSeat::class)($row);
                    }),
            ]);
    }
}
