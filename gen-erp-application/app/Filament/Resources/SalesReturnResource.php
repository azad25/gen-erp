<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReturnResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesReturn;
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

class SalesReturnResource extends BaseResource
{
    protected static ?string $model = SalesReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('Sales Returns');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('invoice_id')->label(__('Invoice'))->options(Invoice::pluck('invoice_number', 'id'))->searchable(),
            Select::make('customer_id')->label(__entity('customer'))->options(Customer::active()->pluck('name', 'id'))->searchable(),
            Select::make('warehouse_id')->label(__('Warehouse'))->options(Warehouse::active()->pluck('name', 'id'))->required()->searchable(),
            DatePicker::make('return_date')->label(__('Return Date'))->required()->default(now()),
            Textarea::make('reason')->label(__('Reason'))->maxLength(2000),
            Repeater::make('items')->relationship()->schema([
                Select::make('product_id')->label(__entity('product'))->options(Product::active()->pluck('name', 'id'))->searchable(),
                TextInput::make('description')->label(__('Description'))->required()->maxLength(500),
                TextInput::make('quantity')->label(__('Qty'))->numeric()->required()->default(1),
                TextInput::make('unit_price')->label(__('Unit Price (৳)'))->numeric()->required()->prefix('৳'),
            ])->columns(2)->minItems(1)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('return_number')->label(__('Return #'))->searchable()->sortable(),
            TextColumn::make('customer.name')->label(__entity('customer'))->searchable(),
            TextColumn::make('return_date')->label(__('Date'))->date('d M Y')->sortable(),
            TextColumn::make('total_amount')->label(__('Total'))->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))->sortable(),
            TextColumn::make('status')->label(__('Status'))->badge(),
        ])->defaultSort('created_at', 'desc')->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesReturns::route('/'),
            'create' => Pages\CreateSalesReturn::route('/create'),
            'edit' => Pages\EditSalesReturn::route('/{record}/edit'),
        ];
    }
}
