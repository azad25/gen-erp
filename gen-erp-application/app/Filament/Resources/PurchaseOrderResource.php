<?php

namespace App\Filament\Resources;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __entity('purchase_order', plural: true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('PurchaseOrder')->schema([
                Tab::make(__('Order Details'))->schema([
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
                    DatePicker::make('order_date')
                        ->label(__('Order Date'))
                        ->required()
                        ->default(now()),
                    DatePicker::make('expected_delivery_date')
                        ->label(__('Expected Delivery')),
                    TextInput::make('supplier_reference')
                        ->label(__('Supplier Ref #'))
                        ->maxLength(100),
                    TextInput::make('shipping_amount')
                        ->label(__('Shipping (৳)'))
                        ->numeric()
                        ->prefix('৳')
                        ->default(0),
                    Textarea::make('notes')
                        ->label(__('Notes'))
                        ->maxLength(2000),
                    Textarea::make('terms_conditions')
                        ->label(__('Terms & Conditions'))
                        ->maxLength(5000),
                ]),
                Tab::make(__('Line Items'))->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('product_id')
                                ->label(__entity('product'))
                                ->options(Product::active()->pluck('name', 'id'))
                                ->searchable()
                                ->reactive(),
                            TextInput::make('description')
                                ->label(__('Description'))
                                ->required()
                                ->maxLength(500),
                            TextInput::make('quantity_ordered')
                                ->label(__('Qty'))
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
                            TextInput::make('discount_percent')
                                ->label(__('Discount %'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('tax_rate')
                                ->label(__('Tax %'))
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(4)
                        ->minItems(1)
                        ->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label(__('Ref #'))->searchable()->sortable(),
                TextColumn::make('supplier.name')->label(__entity('supplier'))->searchable()->sortable(),
                TextColumn::make('order_date')->label(__('Date'))->date('d M Y')->sortable(),
                TextColumn::make('expected_delivery_date')->label(__('Expected'))->date('d M Y')->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('Total'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (PurchaseOrderStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (PurchaseOrderStatus $state): string => $state->color()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PurchaseOrderStatus::options()),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
