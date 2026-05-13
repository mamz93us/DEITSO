<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SlaPolicyResource\Pages;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SlaPolicyResource extends Resource
{
    use Translatable;

    protected static ?string $model = SlaPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Tickets';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'SLA policies';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Policy')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpan(1),
                    Select::make('priority')
                        ->options([
                            Ticket::PRIORITY_LOW => 'Low',
                            Ticket::PRIORITY_NORMAL => 'Normal',
                            Ticket::PRIORITY_HIGH => 'High',
                            Ticket::PRIORITY_URGENT => 'Urgent',
                            Ticket::PRIORITY_CRITICAL => 'Critical',
                        ])
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('response_minutes')->numeric()->minValue(1)->required()->columnSpan(1),
                    TextInput::make('resolution_minutes')->numeric()->minValue(1)->required()->columnSpan(1),

                    Toggle::make('business_hours_only')->inline(false)->columnSpan(1),
                    Toggle::make('is_default')->inline(false)->columnSpan(1),

                    KeyValue::make('business_hours')
                        ->keyLabel('Day')
                        ->valueLabel('Hours (e.g. 09:00-17:00)')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            BadgeColumn::make('priority')->colors([
                'gray' => Ticket::PRIORITY_LOW,
                'primary' => Ticket::PRIORITY_NORMAL,
                'warning' => Ticket::PRIORITY_HIGH,
                'danger' => [Ticket::PRIORITY_URGENT, Ticket::PRIORITY_CRITICAL],
            ]),
            TextColumn::make('response_minutes')->label('Response (min)'),
            TextColumn::make('resolution_minutes')->label('Resolution (min)'),
            IconColumn::make('business_hours_only')->boolean(),
            IconColumn::make('is_default')->boolean(),
        ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSlaPolicies::route('/'),
            'create' => Pages\CreateSlaPolicy::route('/create'),
            'edit' => Pages\EditSlaPolicy::route('/{record}/edit'),
        ];
    }
}
