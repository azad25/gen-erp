<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierPaymentResource\Pages;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Supplier Payments');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('supplier_id')
                ->label(__entity('supplier'))
                ->options(Supplier::active()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            DatePicker::make('payment_date')
                ->label(__('Payment Date'))
                ->required()
                ->default(now()),
            TextInput::make('gross_amount')
                ->label(__('Gross Amount (৳)'))
                ->numeric()
                ->required()
                ->prefix('৳'),
            TextInput::make('tds_amount')
                ->label(__('TDS Amount (৳)'))
                ->numeric()
                ->default(0)
                ->prefix('৳'),
            TextInput::make('vds_amount')
                ->label(__('VDS Amount (৳)'))
                ->numeric()
                ->default(0)
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
        return $table
            ->columns([
                TextColumn::make('payment_number')->label(__('Payment #'))->searchable()->sortable(),
                TextColumn::make('supplier.name')->label(__entity('supplier'))->searchable()->sortable(),
                TextColumn::make('payment_date')->label(__('Date'))->date('d M Y')->sortable(),
                TextColumn::make('gross_amount')
                    ->label(__('Gross'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('net_amount')
                    ->label(__('Net'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierPayments::route('/'),
            'create' => Pages\CreateSupplierPayment::route('/create'),
            'edit' => Pages\EditSupplierPayment::route('/{record}/edit'),
        ];
    }
}
