<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockLevelResource\Pages;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockLevelResource extends Resource
{
    protected static ?string $model = StockLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Current Stock';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label(__('Product'))->searchable()->sortable(),
                TextColumn::make('product.sku')->label(__('SKU'))->searchable(),
                TextColumn::make('warehouse.name')->label(__('Warehouse'))->sortable(),
                TextColumn::make('quantity')->label(__('On Hand'))->sortable()
                    ->numeric(4),
                TextColumn::make('reserved_quantity')->label(__('Reserved'))
                    ->numeric(4),
                TextColumn::make('available')
                    ->label(__('Available'))
                    ->getStateUsing(fn (StockLevel $record): float => $record->availableQuantity())
                    ->numeric(4)
                    ->color(fn (StockLevel $record): string => $record->isLowStock() ? 'danger' : 'success'),
                TextColumn::make('low_stock')
                    ->label(__('Status'))
                    ->getStateUsing(fn (StockLevel $record): string => $record->isLowStock() ? __('Low Stock') : __('OK'))
                    ->badge()
                    ->color(fn (StockLevel $record): string => $record->isLowStock() ? 'danger' : 'success'),
            ])
            ->defaultSort('product.name')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label(__('Warehouse'))
                    ->options(Warehouse::pluck('name', 'id')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLevels::route('/'),
        ];
    }
}
