<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactGroupResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\ContactGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactGroupResource extends BaseResource
{
    protected static ?string $model = ContactGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Contact Groups';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label(__('Group Name'))
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->label(__('Type'))
                ->options([
                    'customer' => __entity('customer'),
                    'supplier' => __entity('supplier'),
                    'both' => __('Both'),
                ])
                ->required(),
            Textarea::make('description')
                ->label(__('Description'))
                ->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('type')->label(__('Type'))->badge()->sortable(),
                TextColumn::make('customers_count')->label(__('Customers'))->counts('customers'),
                TextColumn::make('suppliers_count')->label(__('Suppliers'))->counts('suppliers'),
            ])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactGroups::route('/'),
            'create' => Pages\CreateContactGroup::route('/create'),
            'edit' => Pages\EditContactGroup::route('/{record}/edit'),
        ];
    }
}
