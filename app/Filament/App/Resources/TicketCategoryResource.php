<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TicketCategoryResource\Pages;
use App\Models\TicketCategory;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketCategoryResource extends Resource
{
    use Translatable;

    protected static ?string $model = TicketCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Tickets';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Categories';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Category')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpan(1),
                    TextInput::make('code')->required()->alphaDash()->maxLength(32)->columnSpan(1),
                    Select::make('parent_id')
                        ->label('Parent')
                        ->options(fn ($livewire) => TicketCategory::query()
                            ->when($livewire->record ?? null, fn ($q, $rec) => $q->whereKeyNot($rec))
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->name]))
                        ->searchable()
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->badge()->searchable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('parent.name')->label('Parent')->placeholder('— Top level'),
            TextColumn::make('tickets_count')->counts('tickets')->label('Tickets'),
        ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTicketCategories::route('/'),
            'create' => Pages\CreateTicketCategory::route('/create'),
            'edit' => Pages\EditTicketCategory::route('/{record}/edit'),
        ];
    }
}
