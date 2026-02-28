<?php

namespace App\Filament\Resources;

use App\Enums\JournalEntryStatus;
use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\Account;
use App\Models\JournalEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Journal Entries');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('entry_number')->label(__('Entry Number'))->disabled(),
            DatePicker::make('entry_date')->label(__('Entry Date'))->required()->default(now()),
            TextInput::make('description')->label(__('Description'))->required()->maxLength(500),
            Repeater::make('lines')->label(__('Lines'))->relationship('lines')->schema([
                Select::make('account_id')->label(__('Account'))->options(Account::active()->pluck('name', 'id'))->searchable()->required(),
                TextInput::make('description')->label(__('Description'))->maxLength(500),
                TextInput::make('debit')->label(__('Debit'))->numeric()->default(0)->prefix('৳'),
                TextInput::make('credit')->label(__('Credit'))->numeric()->default(0)->prefix('৳'),
            ])->columns(4)->minItems(2)->defaultItems(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('entry_number')->label(__('Entry #'))->searchable()->sortable(),
            TextColumn::make('entry_date')->label(__('Date'))->date('d M Y')->sortable(),
            TextColumn::make('description')->label(__('Description'))->limit(50),
            TextColumn::make('reference_type')->label(__('Reference')),
            TextColumn::make('status')->label(__('Status'))->formatStateUsing(fn (JournalEntryStatus $state) => $state->label())->badge()->color(fn (JournalEntryStatus $state) => $state->color()),
        ])->defaultSort('entry_date', 'desc')
            ->filters([SelectFilter::make('status')->options(JournalEntryStatus::options())])
            ->actions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
        ];
    }
}
