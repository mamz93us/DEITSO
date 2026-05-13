<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DepartmentResource\Pages;
use App\Models\Department;
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

class DepartmentResource extends Resource
{
    use Translatable;

    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Department')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->helperText('Switch locale top-right to enter Arabic.')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('code')
                        ->label('Short code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(32)
                        ->columnSpan(1),

                    Select::make('parent_id')
                        ->label('Parent department')
                        ->helperText('Leave empty for a top-level department.')
                        ->options(fn ($livewire) => Department::query()
                            ->when($livewire->record ?? null, fn ($q, $rec) => $q->whereKeyNot($rec))
                            ->get()
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('parent.name')->label('Parent')->placeholder('— Top level')->toggleable(),
                TextColumn::make('employees_count')->counts('employees')->label('Employees')->sortable(),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(),
            ])
            ->defaultSort('code')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
