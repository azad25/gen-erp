<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\ProductCategory;
use App\Models\TaxGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductCategoryResource extends BaseResource
{
    protected static ?string $model = ProductCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('parent_id')
                ->label(__('Parent Category'))
                ->options(function (?ProductCategory $record): array {
                    $query = ProductCategory::all();
                    if ($record !== null) {
                        // Exclude self and own children to prevent circular references
                        $query = $query->filter(
                            fn (ProductCategory $c) => $c->id !== $record->id
                                && $c->parent_id !== $record->id
                        );
                    }

                    return $query->pluck('name', 'id')->all();
                })
                ->searchable()
                ->nullable(),
            TextInput::make('name')
                ->label(__('Category Name'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set): void {
                    $set('slug', Str::slug($state));
                }),
            TextInput::make('slug')
                ->label(__('Slug'))
                ->required()
                ->maxLength(255)
                ->unique(table: 'product_categories', column: 'slug', ignoreRecord: true),
            Textarea::make('description')
                ->label(__('Description'))
                ->maxLength(2000),
            FileUpload::make('image_url')
                ->label(__('Category Image'))
                ->image()
                ->directory(fn (): string => 'product-images/'.activeCompany()->id)
                ->visibility('private')
                ->maxSize(10240),
            Select::make('tax_group_id')
                ->label(__('Tax Group'))
                ->options(TaxGroup::withoutGlobalScopes()->pluck('name', 'id'))
                ->nullable()
                ->searchable(),
            Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
            TextInput::make('display_order')
                ->label(__('Display Order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (ProductCategory $record): string {
                        $indent = $record->parent_id ? '↳ ' : '';

                        return $indent.$record->name;
                    }),
                TextColumn::make('parent.name')
                    ->label(__('Parent'))
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('products_count')
                    ->label(__('Products'))
                    ->counts('products')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                TextColumn::make('display_order')
                    ->label(__('Order'))
                    ->sortable(),
            ])
            ->defaultSort('display_order')
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
