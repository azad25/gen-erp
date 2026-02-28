<?php

namespace App\Filament\Resources;

use App\Enums\GoodsReceiptStatus;
use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Supplier;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Goods Receipts');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('supplier_id')
                ->label(__entity('supplier'))
                ->options(Supplier::active()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Select::make('warehouse_id')
                ->label(__('Warehouse'))
                ->options(Warehouse::active()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            DatePicker::make('receipt_date')
                ->label(__('Receipt Date'))
                ->required()
                ->default(now()),
            TextInput::make('supplier_invoice_number')
                ->label(__('Supplier Invoice #'))
                ->maxLength(100),
            DatePicker::make('supplier_invoice_date')
                ->label(__('Supplier Invoice Date')),
            Textarea::make('notes')
                ->label(__('Notes'))
                ->maxLength(2000),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('product_id')
                        ->label(__entity('product'))
                        ->options(Product::active()->pluck('name', 'id'))
                        ->searchable(),
                    TextInput::make('description')
                        ->label(__('Description'))
                        ->required()
                        ->maxLength(500),
                    TextInput::make('quantity_received')
                        ->label(__('Qty Received'))
                        ->numeric()
                        ->required()
                        ->minValue(0.0001)
                        ->default(1),
                    TextInput::make('unit')
                        ->label(__('Unit'))
                        ->default('pcs')
                        ->maxLength(50),
                    TextInput::make('unit_cost')
                        ->label(__('Unit Cost (৳)'))
                        ->numeric()
                        ->required()
                        ->prefix('৳'),
                    TextInput::make('tax_rate')
                        ->label(__('Tax %'))
                        ->numeric()
                        ->default(0),
                ])
                ->columns(3)
                ->minItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')->label(__('GRN #'))->searchable()->sortable(),
                TextColumn::make('supplier.name')->label(__entity('supplier'))->searchable()->sortable(),
                TextColumn::make('receipt_date')->label(__('Date'))->date('d M Y')->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('Total'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (GoodsReceiptStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (GoodsReceiptStatus $state): string => $state->color()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(GoodsReceiptStatus::options()),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
