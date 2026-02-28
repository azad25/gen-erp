<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseReturnResource\Pages;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseReturn;
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
use Filament\Tables\Table;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static ?string $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Purchase Returns');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('goods_receipt_id')->label(__('GRN'))->options(GoodsReceipt::pluck('receipt_number', 'id'))->searchable(),
            Select::make('supplier_id')->label(__entity('supplier'))->options(Supplier::active()->pluck('name', 'id'))->searchable(),
            Select::make('warehouse_id')->label(__('Warehouse'))->options(Warehouse::active()->pluck('name', 'id'))->required()->searchable(),
            DatePicker::make('return_date')->label(__('Return Date'))->required()->default(now()),
            Textarea::make('reason')->label(__('Reason'))->maxLength(2000),
            Repeater::make('items')->relationship()->schema([
                Select::make('product_id')->label(__entity('product'))->options(Product::active()->pluck('name', 'id'))->searchable(),
                TextInput::make('description')->label(__('Description'))->required()->maxLength(500),
                TextInput::make('quantity')->label(__('Qty'))->numeric()->required()->default(1),
                TextInput::make('unit_cost')->label(__('Unit Cost (৳)'))->numeric()->required()->prefix('৳'),
            ])->columns(2)->minItems(1)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('return_number')->label(__('Return #'))->searchable()->sortable(),
            TextColumn::make('supplier.name')->label(__entity('supplier'))->searchable(),
            TextColumn::make('return_date')->label(__('Date'))->date('d M Y')->sortable(),
            TextColumn::make('total_amount')->label(__('Total'))->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))->sortable(),
            TextColumn::make('status')->label(__('Status'))->badge(),
        ])->defaultSort('created_at', 'desc')->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
