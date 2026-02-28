<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerPaymentResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerPaymentResource extends BaseResource
{
    protected static ?string $model = CustomerPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Customer Payments');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('customer_id')
                ->label(__entity('customer'))
                ->options(Customer::active()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            DatePicker::make('payment_date')
                ->label(__('Payment Date'))
                ->required()
                ->default(now()),
            TextInput::make('amount')
                ->label(__('Amount (৳)'))
                ->numeric()
                ->required()
                ->prefix('৳'),
            Select::make('payment_method_id')
                ->label(__('Payment Method'))
                ->options(PaymentMethod::active()->pluck('name', 'id'))
                ->searchable(),
            TextInput::make('reference_number')
                ->label(__('Reference #'))
                ->maxLength(100),
            Textarea::make('notes')
                ->label(__('Notes'))
                ->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('receipt_number')->label(__('Receipt #'))->searchable()->sortable(),
                TextColumn::make('customer.name')->label(__entity('customer'))->searchable()->sortable(),
                TextColumn::make('payment_date')->label(__('Date'))->date('d M Y')->sortable(),
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('paymentMethod.name')->label(__('Method')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerPayments::route('/'),
            'create' => Pages\CreateCustomerPayment::route('/create'),
            'edit' => Pages\EditCustomerPayment::route('/{record}/edit'),
        ];
    }
}
