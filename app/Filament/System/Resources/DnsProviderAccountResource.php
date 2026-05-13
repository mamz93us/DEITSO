<?php

declare(strict_types=1);

namespace App\Filament\System\Resources;

use App\Filament\System\Resources\DnsProviderAccountResource\Pages;
use App\Models\DnsProviderAccount;
use Filament\Forms\Components\KeyValue;
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
use Filament\Tables\Table;

class DnsProviderAccountResource extends Resource
{
    protected static ?string $model = DnsProviderAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'DNS Providers';

    protected static ?string $modelLabel = 'DNS Provider';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Provider')
                ->columns(2)
                ->schema([
                    Select::make('provider')
                        ->options([
                            DnsProviderAccount::PROVIDER_GODADDY => 'GoDaddy',
                            DnsProviderAccount::PROVIDER_CLOUDFLARE => 'Cloudflare',
                            DnsProviderAccount::PROVIDER_ROUTE53 => 'AWS Route 53',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('environment')
                        ->options([
                            DnsProviderAccount::ENV_PRODUCTION => 'Production',
                            DnsProviderAccount::ENV_OTE => 'OTE (sandbox)',
                            DnsProviderAccount::ENV_SANDBOX => 'Sandbox',
                        ])
                        ->default(DnsProviderAccount::ENV_OTE)
                        ->required()
                        ->native(false),

                    TextInput::make('name')
                        ->label('Display name')
                        ->helperText('Internal label, e.g. "GoDaddy OTE"')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('base_domain')
                        ->label('Base domain')
                        ->helperText('Subdomains will be created under this — e.g. it.deevar.cloud')
                        ->required()
                        ->maxLength(255),

                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'active' => 'Active',
                            'disabled' => 'Disabled',
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false),

                    Toggle::make('is_default')
                        ->label('Default for new organizations')
                        ->helperText('Only one account can be the default')
                        ->inline(false),
                ]),

            Section::make('Credentials')
                ->description('Stored encrypted at rest. Never logged.')
                ->schema([
                    KeyValue::make('credentials_encrypted')
                        ->label('API Credentials')
                        ->keyLabel('Field')
                        ->valueLabel('Value')
                        ->keyPlaceholder('api_key, api_secret, …')
                        ->valuePlaceholder('value')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                BadgeColumn::make('provider')
                    ->colors([
                        'primary' => 'godaddy',
                        'warning' => 'cloudflare',
                        'success' => 'route53',
                    ]),
                BadgeColumn::make('environment')
                    ->colors([
                        'danger' => 'production',
                        'warning' => 'ote',
                        'gray' => 'sandbox',
                    ]),
                TextColumn::make('base_domain')->searchable(),
                IconColumn::make('is_default')->boolean()->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'disabled',
                    ]),
                TextColumn::make('last_check_at')->dateTime()->since()->toggleable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_default', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDnsProviderAccounts::route('/'),
            'create' => Pages\CreateDnsProviderAccount::route('/create'),
            'edit' => Pages\EditDnsProviderAccount::route('/{record}/edit'),
        ];
    }
}
