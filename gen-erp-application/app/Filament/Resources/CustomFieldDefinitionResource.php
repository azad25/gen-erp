<?php

namespace App\Filament\Resources;

use App\Enums\CustomFieldType;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Filament\Resources\CustomFieldDefinitionResource\Pages;
use App\Models\CustomFieldDefinition;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CustomFieldDefinitionResource extends BaseResource
{
    protected static ?string $model = CustomFieldDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Custom Fields';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'settings/custom-fields';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('entity_type')
                ->label(__('Entity Type'))
                ->options([
                    'product' => __entity('product'),
                    'customer' => __entity('customer'),
                    'supplier' => __entity('supplier'),
                    'invoice' => __entity('invoice'),
                    'purchase_order' => __entity('purchase_order'),
                    'expense' => __entity('expense'),
                    'employee' => __entity('employee'),
                ])
                ->required()
                ->disabled(fn (?CustomFieldDefinition $record): bool => $record !== null),
            TextInput::make('label')
                ->label(__('Label'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $set, ?CustomFieldDefinition $record): void {
                    if (! $record) {
                        $set('field_key', Str::snake($state));
                    }
                }),
            TextInput::make('field_key')
                ->label(__('Field Key'))
                ->required()
                ->maxLength(100)
                ->disabled(fn (?CustomFieldDefinition $record): bool => $record !== null)
                ->dehydrated()
                ->helperText(__('Auto-generated from label. Cannot be changed after creation.')),
            Select::make('field_type')
                ->label(__('Field Type'))
                ->options(CustomFieldType::options())
                ->required()
                ->live()
                ->disabled(fn (?CustomFieldDefinition $record): bool => $record !== null),
            KeyValue::make('options')
                ->label(__('Options (value → label)'))
                ->keyLabel(__('Value'))
                ->valueLabel(__('Label'))
                ->visible(fn ($get): bool => in_array($get('field_type'), ['select', 'multiselect'])),
            Toggle::make('is_required')
                ->label(__('Required')),
            Toggle::make('show_in_list')
                ->label(__('Show in List')),
            Toggle::make('is_filterable')
                ->label(__('Filterable'))
                ->helperText(__('Enabling this runs a background job to create a database index. May take a moment for large datasets.')),
            Toggle::make('is_searchable')
                ->label(__('Searchable')),
            TextInput::make('display_order')
                ->label(__('Display Order'))
                ->numeric()
                ->default(0),
            TextInput::make('default_value')
                ->label(__('Default Value'))
                ->maxLength(10000),
            Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('label')
                    ->label(__('Label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entity_type')
                    ->label(__('Entity'))
                    ->formatStateUsing(fn (string $state): string => __entity($state))
                    ->sortable(),
                TextColumn::make('field_type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn (CustomFieldType $state): string => $state->label())
                    ->sortable(),
                IconColumn::make('is_required')
                    ->label(__('Required'))
                    ->boolean(),
                IconColumn::make('is_filterable')
                    ->label(__('Filterable'))
                    ->boolean(),
                IconColumn::make('show_in_list')
                    ->label(__('In List'))
                    ->boolean(),
                TextColumn::make('display_order')
                    ->label(__('Order'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->defaultSort('display_order')
            ->reorderable('display_order')
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomFieldDefinitions::route('/'),
            'create' => Pages\CreateCustomFieldDefinition::route('/create'),
            'edit' => Pages\EditCustomFieldDefinition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->orderBy('display_order');
    }
}
