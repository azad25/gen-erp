<?php

namespace App\Filament\Resources;

use App\Enums\ProductType;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Services\CustomFieldService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __entity('product', plural: true);
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        /** @var Product $record */
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'barcode'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            FormStyles::tabs([
                Tabs\Tab::make(__('Details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Product Name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get): void {
                                if (! $get('slug')) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'products',
                                column: 'slug',
                                ignoreRecord: true
                            ),
                        TextInput::make('sku')
                            ->label(__('SKU'))
                            ->maxLength(100)
                            ->unique(
                                table: 'products',
                                column: 'sku',
                                ignoreRecord: true
                            ),
                        TextInput::make('barcode')
                            ->label(__('Barcode'))
                            ->maxLength(100),
                        Select::make('product_type')
                            ->label(__('Product Type'))
                            ->options(ProductType::options())
                            ->default(ProductType::PRODUCT->value)
                            ->required()
                            ->live(),
                        Select::make('category_id')
                            ->label(__entity('product_category'))
                            ->options(
                                ProductCategory::withoutGlobalScopes()
                                    ->where('company_id', activeCompany()->id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->nullable(),
                        Select::make('unit')
                            ->label(__('Unit'))
                            ->options(
                                Unit::withoutGlobalScopes()
                                    ->where('company_id', activeCompany()->id)
                                    ->pluck('abbreviation', 'abbreviation')
                            )
                            ->default('pcs')
                            ->createOptionForm([
                                TextInput::make('name')->required()->maxLength(100),
                                TextInput::make('abbreviation')->required()->maxLength(20),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                Unit::withoutGlobalScopes()->create([
                                    'company_id' => activeCompany()->id,
                                    'name' => $data['name'],
                                    'abbreviation' => $data['abbreviation'],
                                ]);

                                return $data['abbreviation'];
                            }),
                        RichEditor::make('description')
                            ->label(__('Description'))
                            ->columnSpanFull(),
                        FileUpload::make('image_url')
                            ->label(__('Product Image'))
                            ->image()
                            ->directory(fn (): string => 'product-images/'.activeCompany()->id)
                            ->visibility('private')
                            ->maxSize(10240),
                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                        Toggle::make('track_inventory')
                            ->label(__('Track Inventory'))
                            ->default(true)
                            ->visible(fn ($get): bool => ProductType::tryFrom($get('product_type') ?? 'product')?->tracksInventory() ?? true),
                    ])
                    ->columns(2),
                Tabs\Tab::make(__('Pricing'))
                    ->schema([
                        TextInput::make('cost_price')
                            ->label(__('Cost Price (৳)'))
                            ->numeric()
                            ->prefix('৳')
                            ->default(0)
                            ->helperText(__('Enter in Taka (e.g. 150.00)')),
                        TextInput::make('selling_price')
                            ->label(__('Selling Price (৳)'))
                            ->numeric()
                            ->prefix('৳')
                            ->default(0)
                            ->required(),
                        TextInput::make('min_selling_price')
                            ->label(__('Min Selling Price (৳)'))
                            ->numeric()
                            ->prefix('৳')
                            ->default(0)
                            ->helperText(__('Floor price — cannot sell below this')),
                        Select::make('tax_group_id')
                            ->label(__('Tax Group'))
                            ->options(
                                TaxGroup::withoutGlobalScopes()
                                    ->where('company_id', activeCompany()->id)
                                    ->pluck('name', 'id')
                            )
                            ->nullable()
                            ->searchable(),
                        Placeholder::make('profit_margin_display')
                            ->label(__('Profit Margin'))
                            ->content(function ($get): string {
                                $cost = (float) ($get('cost_price') ?? 0);
                                $selling = (float) ($get('selling_price') ?? 0);
                                if ($cost <= 0) {
                                    return '—';
                                }

                                $margin = round((($selling - $cost) / $cost) * 100, 2);

                                return "Margin: {$margin}%";
                            })
                            ->live(),
                    ])
                    ->columns(2),
                Tabs\Tab::make(__('Custom Fields'))
                    ->schema(function (): array {
                        $service = app(CustomFieldService::class);
                        $components = $service->buildFormComponents('product');

                        if (empty($components)) {
                            return [
                                Placeholder::make('no_custom_fields')
                                    ->label('')
                                    ->content(__('No custom fields configured. Go to Settings > Custom Fields to add them.')),
                            ];
                        }

                        return $components;
                    }),
            ])
        ])->columns(static::modernFormColumns());
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                ImageColumn::make('image_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?name=P&background=random'),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('sku')
                    ->label(__('SKU'))
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('category.name')
                    ->label(__entity('product_category'))
                    ->sortable()
                    ->placeholder('—')
                    ->badge()
                    ->color('info'),
                TableStyles::money('selling_price', __('Selling Price')),
                TableStyles::enum('product_type', __('Type'))
                    ->formatStateUsing(fn (ProductType $state): string => $state->label())
                    ->color(fn (ProductType $state): string => match ($state) {
                        ProductType::SERVICE => 'info',
                        ProductType::DIGITAL => 'warning',
                        default => 'success',
                    }),
                TableStyles::boolean('is_active', __('Active')),
                TableStyles::datetime('created_at'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('product_type')
                    ->options(ProductType::options())
                    ->multiple(),
                SelectFilter::make('is_active')
                    ->options([
                        1 => __('Active'),
                        0 => __('Inactive'),
                    ]),
            ])
            ->defaultSort('name')
            ->actions(static::getModernTableActions())
            ->bulkActions(static::getModernBulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
