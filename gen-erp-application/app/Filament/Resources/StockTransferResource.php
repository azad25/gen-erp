<?php

namespace App\Filament\Resources;

use App\Enums\StockTransferStatus;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockTransferResource extends BaseResource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Transfers';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('from_warehouse_id')
                ->label(__('From Warehouse'))
                ->options(Warehouse::active()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->different('to_warehouse_id'),
            Select::make('to_warehouse_id')
                ->label(__('To Warehouse'))
                ->options(Warehouse::active()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->different('from_warehouse_id'),
            DatePicker::make('transfer_date')
                ->label(__('Transfer Date'))
                ->required()
                ->default(now()),
            Textarea::make('notes')
                ->label(__('Notes'))
                ->maxLength(2000),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('product_id')
                        ->label(__('Product'))
                        ->options(Product::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('quantity_sent')
                        ->label(__('Qty to Send'))
                        ->numeric()
                        ->required()
                        ->minValue(0.0001),
                ])
                ->columns(2)
                ->minItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('reference_number')->label(__('Ref #'))->searchable()->sortable(),
                TextColumn::make('fromWarehouse.name')->label(__('From'))->sortable(),
                TextColumn::make('toWarehouse.name')->label(__('To'))->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (StockTransferStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (StockTransferStatus $state): string => $state->color()),
                TextColumn::make('transfer_date')->label(__('Date'))->date('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
