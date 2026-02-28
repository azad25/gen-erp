<?php

namespace App\Filament\Resources;

use App\Enums\AccountSubType;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Enums\AccountType;
use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use App\Models\AccountGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountResource extends BaseResource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Chart of Accounts');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('account_group_id')->label(__('Account Group'))->options(AccountGroup::pluck('name', 'id'))->searchable()->nullable(),
            TextInput::make('code')->label(__('Account Code'))->required()->maxLength(20),
            TextInput::make('name')->label(__('Account Name'))->required()->maxLength(255),
            Select::make('account_type')->label(__('Type'))->options(AccountType::options())->required(),
            Select::make('sub_type')->label(__('Sub Type'))->options(AccountSubType::options())->required(),
            TextInput::make('opening_balance')->label(__('Opening Balance (৳)'))->numeric()->default(0)->prefix('৳'),
            Textarea::make('description')->label(__('Description'))->maxLength(2000),
            Toggle::make('is_active')->label(__('Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label(__('Code'))->searchable()->sortable(),
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('account_type')->label(__('Type'))->formatStateUsing(fn (AccountType $state) => $state->label())->badge()->color(fn (AccountType $state) => $state->color()),
            TextColumn::make('sub_type')->label(__('Sub Type'))->formatStateUsing(fn (AccountSubType $state) => $state->label()),
            IconColumn::make('is_system')->label(__('System'))->boolean(),
            IconColumn::make('is_active')->label(__('Active'))->boolean(),
        ])->defaultSort('code')
            ->filters([SelectFilter::make('account_type')->options(AccountType::options())])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
