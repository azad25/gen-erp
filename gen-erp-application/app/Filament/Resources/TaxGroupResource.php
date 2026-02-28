<?php

namespace App\Filament\Resources;

use App\Enums\TaxType;
use App\Filament\Resources\TaxGroupResource\Pages;
use App\Models\TaxGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament resource for managing tax groups (VAT, SD, AIT).
 */
class TaxGroupResource extends BaseResource
{
    protected static ?string $model = TaxGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('Tax Groups');
    }

    public static function getModelLabel(): string
    {
        return __('Tax Group');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', activeCompany()?->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Tax Group Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('type')
                            ->label(__('Tax Type'))
                            ->options(collect(TaxType::cases())->mapWithKeys(fn (TaxType $t) => [$t->value => $t->label()]))
                            ->required()
                            ->default('vat'),

                        Forms\Components\TextInput::make('rate')
                            ->label(__('Rate (%)'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%'),

                        Forms\Components\Toggle::make('is_compound')
                            ->label(__('Compound Tax'))
                            ->helperText(__('If enabled, VAT is calculated on the SD-inclusive amount'))
                            ->default(false),

                        Forms\Components\Toggle::make('is_default')
                            ->label(__('Default'))
                            ->helperText(__('Default tax group for new products'))
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (TaxType $state) => $state->label())
                    ->color(fn (TaxType $state) => match ($state) {
                        TaxType::VAT => 'success',
                        TaxType::SD => 'warning',
                        TaxType::AIT => 'info',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label(__('Rate'))
                    ->formatStateUsing(fn ($state) => number_format($state, 2).'%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_compound')
                    ->label(__('Compound'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('Default'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('Products'))
                    ->counts('products')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(TaxType::cases())->mapWithKeys(fn (TaxType $t) => [$t->value => $t->label()])),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active')),
            ])
            ->actions(static::getModernTableActions())
            ->bulkActions(static::getModernBulkActions())
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxGroups::route('/'),
            'create' => Pages\CreateTaxGroup::route('/create'),
            'edit' => Pages\EditTaxGroup::route('/{record}/edit'),
        ];
    }
}
