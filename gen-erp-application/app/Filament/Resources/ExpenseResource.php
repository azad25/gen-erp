<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseStatus;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Account;
use App\Models\Expense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Expenses');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('expense_number')->label(__('Expense #'))->disabled(),
            DatePicker::make('expense_date')->label(__('Date'))->required()->default(now()),
            Select::make('account_id')->label(__('Expense Account'))->options(Account::active()->ofType('expense')->pluck('name', 'id'))->searchable(),
            Select::make('payment_account_id')->label(__('Payment Account'))->options(Account::active()->whereIn('sub_type', ['cash', 'bank'])->pluck('name', 'id'))->searchable(),
            TextInput::make('category')->label(__('Category'))->maxLength(100),
            TextInput::make('description')->label(__('Description'))->required()->maxLength(500),
            TextInput::make('amount')->label(__('Amount (৳)'))->numeric()->required()->prefix('৳'),
            TextInput::make('tax_amount')->label(__('Tax (৳)'))->numeric()->default(0)->prefix('৳'),
            TextInput::make('total_amount')->label(__('Total (৳)'))->numeric()->required()->prefix('৳'),
            TextInput::make('reference_number')->label(__('Reference'))->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('expense_number')->label(__('Expense #'))->searchable()->sortable(),
            TextColumn::make('expense_date')->label(__('Date'))->date('d M Y')->sortable(),
            TextColumn::make('description')->label(__('Description'))->limit(40),
            TextColumn::make('category')->label(__('Category')),
            TextColumn::make('total_amount')->label(__('Amount'))->formatStateUsing(fn (int $state) => '৳'.number_format($state / 100, 2))->sortable(),
            TextColumn::make('status')->label(__('Status'))->formatStateUsing(fn (ExpenseStatus $state) => $state->label())->badge()->color(fn (ExpenseStatus $state) => $state->color()),
        ])->defaultSort('expense_date', 'desc')
            ->filters([SelectFilter::make('status')->options(ExpenseStatus::options())])
            ->actions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
