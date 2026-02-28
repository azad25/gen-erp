<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __entity('invoice', plural: true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Invoice')->schema([
                Tab::make(__('Invoice Details'))->schema([
                    Select::make('customer_id')
                        ->label(__entity('customer'))
                        ->options(Customer::active()->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('warehouse_id')
                        ->label(__('Warehouse'))
                        ->options(Warehouse::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    DatePicker::make('invoice_date')
                        ->label(__('Invoice Date'))
                        ->required()
                        ->default(now()),
                    DatePicker::make('due_date')
                        ->label(__('Due Date'))
                        ->required(),
                    TextInput::make('mushak_number')
                        ->label(__('Mushak 6.3 No'))
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
                            TextInput::make('quantity')
                                ->label(__('Qty'))
                                ->numeric()
                                ->required()
                                ->minValue(0.0001)
                                ->default(1),
                            TextInput::make('unit')
                                ->label(__('Unit'))
                                ->default('pcs')
                                ->maxLength(50),
                            TextInput::make('unit_price')
                                ->label(__('Unit Price (৳)'))
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
                TextColumn::make('invoice_number')->label(__('Invoice #'))->searchable()->sortable(),
                TextColumn::make('customer.name')->label(__entity('customer'))->searchable()->sortable(),
                TextColumn::make('invoice_date')->label(__('Date'))->date('d M Y')->sortable(),
                TextColumn::make('due_date')->label(__('Due'))->date('d M Y')->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('Total'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label(__('Balance'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => $state->color()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::options()),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
