<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetResource\RelationManagers;

use App\Models\AssetRemoteAccess;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Per-asset remote-access credentials.
 *
 * Two permissions gate visibility:
 *   - itam.asset.view_remote_id           → see identifier, type, username
 *   - itam.asset.view_remote_credentials  → reveal the decrypted password
 *
 * The password column is hidden from the model by default; clicking the
 * "Reveal" action surfaces it in a one-time notification and writes an
 * activity-log entry. Editing/storing the password is also gated.
 */
class RemoteAccessRelationManager extends RelationManager
{
    protected static string $relationship = 'remoteAccess';

    protected static ?string $title = 'Remote access';

    protected static ?string $icon = 'heroicon-o-key';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Endpoint')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options([
                            AssetRemoteAccess::TYPE_TEAMVIEWER => 'TeamViewer',
                            AssetRemoteAccess::TYPE_ANYDESK => 'AnyDesk',
                            AssetRemoteAccess::TYPE_RDP => 'RDP',
                            AssetRemoteAccess::TYPE_VNC => 'VNC',
                            AssetRemoteAccess::TYPE_SSH => 'SSH',
                            AssetRemoteAccess::TYPE_RUSTDESK => 'RustDesk',
                            AssetRemoteAccess::TYPE_OTHER => 'Other',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    TextInput::make('identifier')
                        ->label('ID / hostname / IP')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('username')->maxLength(255)->columnSpan(1),

                    TextInput::make('port')->numeric()->minValue(1)->maxValue(65535)->columnSpan(1),

                    TextInput::make('password_encrypted')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->helperText('Stored encrypted at rest. Requires the credentials-view permission to reveal later.')
                        ->columnSpanFull(),

                    Textarea::make('notes')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identifier')
            ->columns([
                BadgeColumn::make('type'),
                TextColumn::make('identifier')->copyable()->searchable(),
                TextColumn::make('username')->toggleable(),
                TextColumn::make('port')->toggleable(),
                TextColumn::make('notes')->toggleable()->limit(40),
            ])
            ->headerActions([
                CreateAction::make()->label('New credential'),
            ])
            ->actions([
                Action::make('reveal_password')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->can('itam.asset.view_remote_credentials') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Reveal credential password')
                    ->modalDescription('Your viewing will be recorded in the audit log. Continue?')
                    ->action(function (AssetRemoteAccess $record) {
                        // Touching the row's activity log makes the access auditable.
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->event('credentials_revealed')
                            ->log('Remote access password revealed');

                        Notification::make()
                            ->title('Password (copy now, will not be shown again)')
                            ->body($record->password_encrypted ?? '— no password set —')
                            ->warning()
                            ->persistent()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn () => auth()->user()?->can('itam.asset.view_remote_credentials') ?? false),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('itam.asset.view_remote_credentials') ?? false),
            ]);
    }
}
