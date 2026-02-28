<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\Warehouse;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseResource extends BaseResource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Warehouses';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label(__('Warehouse Name'))
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label(__('Code'))
                ->required()
                ->maxLength(50),
            Textarea::make('address')
                ->label(__('Address'))
                ->maxLength(2000),
            Toggle::make('is_default')
                ->label(__('Default Warehouse')),
            Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('code')->label(__('Code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                IconColumn::make('is_default')->label(__('Default'))->boolean(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
