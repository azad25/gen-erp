<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntityAliasResource\Pages;
use App\Models\EntityAlias;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

/**
 * Filament resource for managing entity aliases (e.g. Customer → Client).
 * Pro+ plan only. Changes invalidate the entity alias Redis cache.
 */
class EntityAliasResource extends BaseResource
{
    protected static ?string $model = EntityAlias::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'settings/entity-aliases';

    /**
     * The 9 supported entity types.
     *
     * @var array<string, string>
     */
    private const ENTITY_TYPES = [
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'product' => 'Product',
        'invoice' => 'Invoice',
        'purchase_order' => 'Purchase Order',
        'expense' => 'Expense',
        'employee' => 'Employee',
        'sales_order' => 'Sales Order',
        'warehouse' => 'Warehouse',
    ];

    public static function getNavigationLabel(): string
    {
        return __('Entity Aliases');
    }

    public static function getModelLabel(): string
    {
        return __('Entity Alias');
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
                Forms\Components\Section::make(__('Entity Alias'))
                    ->description(__('Rename system entities to match your business terminology'))
                    ->schema([
                        Forms\Components\Select::make('entity_key')
                            ->label(__('Entity'))
                            ->options(self::ENTITY_TYPES)
                            ->required()
                            ->disabled(fn (?EntityAlias $record): bool => $record !== null)
                            ->helperText(__('Select the entity to rename')),

                        Forms\Components\TextInput::make('alias')
                            ->label(__('Custom Name (Singular)'))
                            ->required()
                            ->maxLength(100)
                            ->helperText(__('e.g. "Client" instead of "Customer"')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entity_key')
                    ->label(__('Original Name'))
                    ->formatStateUsing(fn (string $state) => self::ENTITY_TYPES[$state] ?? $state)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('alias')
                    ->label(__('Custom Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Changed'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions(static::getModernTableActions())
            ->bulkActions(static::getModernBulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntityAliases::route('/'),
            'create' => Pages\CreateEntityAlias::route('/create'),
            'edit' => Pages\EditEntityAlias::route('/{record}/edit'),
        ];
    }

    /**
     * Invalidate the entity alias cache when aliases change.
     */
    public static function invalidateCache(): void
    {
        $company = activeCompany();
        if ($company) {
            Cache::forget("entity_aliases:{$company->id}");
        }
    }
}
