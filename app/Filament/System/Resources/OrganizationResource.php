<?php

declare(strict_types=1);

namespace App\Filament\System\Resources;

use App\Filament\System\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class OrganizationResource extends Resource
{
    use Translatable;

    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Organization')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Display name')
                        ->helperText('Use the locale switcher (top-right) to enter Arabic.')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $get('slug') && is_string($state) && $state !== '') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->columnSpan(1),

                    TextInput::make('slug')
                        ->label('URL slug')
                        ->helperText('Used as the platform subdomain — e.g. samirgroup.it.deevar.cloud')
                        ->required()
                        ->maxLength(64)
                        ->regex('/^[a-z0-9-]+$/')
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'pending' => 'Pending',
                            'suspended' => 'Suspended',
                        ])
                        ->default('active')
                        ->required()
                        ->columnSpan(1),
                ]),

            Section::make('Settings')
                ->description('Org-wide preferences. Leave empty to use platform defaults.')
                ->collapsed()
                ->schema([
                    KeyValue::make('settings')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->badge()
                    ->copyable()
                    ->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'suspended',
                    ]),
                TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label('Branches')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'suspended' => 'Suspended',
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
