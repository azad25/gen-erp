<?php

namespace App\Filament\Resources;

use App\Enums\CreditNoteStatus;
use App\Filament\Resources\CreditNoteResource\Pages;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
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

class CreditNoteResource extends Resource
{
    protected static ?string $model = CreditNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Credit Notes');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('invoice_id')
                ->label(__('Invoice'))
                ->options(Invoice::pluck('invoice_number', 'id'))
                ->searchable(),
            Select::make('customer_id')
                ->label(__entity('customer'))
                ->options(Customer::active()->pluck('name', 'id'))
                ->searchable(),
            DatePicker::make('credit_date')
                ->label(__('Date'))
                ->required()
                ->default(now()),
            Textarea::make('reason')
                ->label(__('Reason'))
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
                    TextInput::make('quantity')
                        ->label(__('Qty'))
                        ->numeric()
                        ->required()
                        ->default(1),
                    TextInput::make('unit_price')
                        ->label(__('Unit Price (৳)'))
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
                TextColumn::make('credit_note_number')->label(__('CN #'))->searchable()->sortable(),
                TextColumn::make('customer.name')->label(__entity('customer'))->searchable(),
                TextColumn::make('credit_date')->label(__('Date'))->date('d M Y')->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('Total'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (CreditNoteStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (CreditNoteStatus $state): string => $state->color()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([SelectFilter::make('status')->options(CreditNoteStatus::options())])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCreditNotes::route('/'),
            'create' => Pages\CreateCreditNote::route('/create'),
            'edit' => Pages\EditCreditNote::route('/{record}/edit'),
        ];
    }
}
