<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\OrganizationDomainResource\Pages;
use App\Models\DnsProviderAccount;
use App\Models\OrganizationDomain;
use Filament\Forms\Components\DateTimePicker;
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

class OrganizationDomainResource extends Resource
{
    protected static ?string $model = OrganizationDomain::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Domains';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Domain')
                ->columns(2)
                ->schema([
                    TextInput::make('host')
                        ->label('Hostname')
                        ->helperText('e.g. samirgroup.it.deevar.cloud or support.clientco.com')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->columnSpanFull(),

                    Select::make('type')
                        ->options([
                            OrganizationDomain::TYPE_PLATFORM => 'Platform subdomain (auto-provisioned)',
                            OrganizationDomain::TYPE_CUSTOM => 'Custom domain (CNAME by client)',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('dns_provider_id')
                        ->label('DNS provider account')
                        ->options(fn () => DnsProviderAccount::query()->pluck('name', 'id'))
                        ->searchable()
                        ->helperText('Only needed for platform subdomains')
                        ->nullable(),

                    Toggle::make('is_primary')
                        ->label('Primary domain for this org')
                        ->inline(false),
                ]),

            Section::make('Verification status')
                ->description('These fields are system-controlled — only the verification job can promote a domain to "verified". Users cannot edit them.')
                ->columns(2)
                ->schema([
                    Select::make('dns_status')
                        ->options([
                            OrganizationDomain::DNS_PENDING => 'Pending propagation',
                            OrganizationDomain::DNS_VERIFIED => 'Verified',
                            OrganizationDomain::DNS_FAILED => 'Failed',
                        ])
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false),

                    Select::make('tls_status')
                        ->options([
                            OrganizationDomain::TLS_PENDING => 'Pending',
                            OrganizationDomain::TLS_PROVISIONING => 'Provisioning',
                            OrganizationDomain::TLS_ACTIVE => 'Active',
                            OrganizationDomain::TLS_FAILED => 'Failed',
                        ])
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false),

                    TextInput::make('verification_token')
                        ->label('TXT verification token')
                        ->helperText('Auto-generated. Client adds this as a TXT record on _deitam-verify.{host}.')
                        ->maxLength(64)
                        ->disabled()
                        ->dehydrated()
                        ->columnSpanFull(),

                    DateTimePicker::make('last_checked_at')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('host')->searchable()->sortable()->copyable(),
                BadgeColumn::make('type')
                    ->colors([
                        'primary' => OrganizationDomain::TYPE_PLATFORM,
                        'warning' => OrganizationDomain::TYPE_CUSTOM,
                    ]),
                BadgeColumn::make('dns_status')
                    ->colors([
                        'success' => OrganizationDomain::DNS_VERIFIED,
                        'warning' => OrganizationDomain::DNS_PENDING,
                        'danger' => OrganizationDomain::DNS_FAILED,
                    ]),
                BadgeColumn::make('tls_status')
                    ->colors([
                        'success' => OrganizationDomain::TLS_ACTIVE,
                        'warning' => [OrganizationDomain::TLS_PROVISIONING, OrganizationDomain::TLS_PENDING],
                        'danger' => OrganizationDomain::TLS_FAILED,
                    ]),
                IconColumn::make('is_primary')->boolean()->sortable(),
                TextColumn::make('last_checked_at')->dateTime()->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        OrganizationDomain::TYPE_PLATFORM => 'Platform subdomain',
                        OrganizationDomain::TYPE_CUSTOM => 'Custom domain',
                    ]),
                SelectFilter::make('dns_status')
                    ->options([
                        OrganizationDomain::DNS_PENDING => 'Pending',
                        OrganizationDomain::DNS_VERIFIED => 'Verified',
                        OrganizationDomain::DNS_FAILED => 'Failed',
                    ]),
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
            ->defaultSort('is_primary', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationDomains::route('/'),
            'create' => Pages\CreateOrganizationDomain::route('/create'),
            'edit' => Pages\EditOrganizationDomain::route('/{record}/edit'),
        ];
    }
}
