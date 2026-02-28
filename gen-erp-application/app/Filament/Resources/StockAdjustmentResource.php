<?php

namespace App\Filament\Resources;

use App\Enums\AdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\Product;
use App\Models\StockAdjustment;
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

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Adjustments';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('warehouse_id')
                ->label(__('Warehouse'))
                ->options(Warehouse::active()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            DatePicker::make('adjustment_date')
                ->label(__('Adjustment Date'))
                ->required()
                ->default(now()),
            Select::make('reason')
                ->label(__('Reason'))
                ->options(AdjustmentReason::options())
                ->required(),
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
                    TextInput::make('current_quantity')
                        ->label(__('Current Qty'))
                        ->numeric()
                        ->default(0),
                    TextInput::make('adjusted_quantity')
                        ->label(__('Adjusted Qty'))
                        ->numeric()
                        ->required(),
                    TextInput::make('unit_cost')
                        ->label(__('Unit Cost (৳)'))
                        ->numeric()
                        ->prefix('৳'),
                ])
                ->columns(4)
                ->minItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label(__('Ref #'))->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label(__('Warehouse'))->sortable(),
                TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->formatStateUsing(fn (AdjustmentReason $state): string => $state->label())
                    ->badge()
                    ->color(fn (AdjustmentReason $state): string => $state->color()),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (StockAdjustmentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (StockAdjustmentStatus $state): string => $state->color()),
                TextColumn::make('adjustment_date')->label(__('Date'))->date('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
