<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeTaxSlabResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\IncomeTaxSlab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncomeTaxSlabResource extends BaseResource
{
    protected static ?string $model = IncomeTaxSlab::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?int $navigationSort = 14;

    public static function getNavigationLabel(): string
    {
        return __('Income Tax Slabs');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('fiscal_year')->label(__('Fiscal Year'))->required()->maxLength(10),
            TextInput::make('min_income')->label(__('Min Income (৳)'))->numeric()->required()->prefix('৳'),
            TextInput::make('max_income')->label(__('Max Income (৳)'))->numeric()->nullable()->prefix('৳'),
            TextInput::make('tax_rate')->label(__('Tax Rate %'))->numeric()->required()->suffix('%'),
            TextInput::make('description')->label(__('Description'))->maxLength(255),
            TextInput::make('display_order')->label(__('Order'))->numeric()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('fiscal_year')->label(__('FY'))->sortable(),
            TextColumn::make('min_income')->label(__('Min'))->formatStateUsing(fn (int $state) => '৳'.number_format($state / 100, 2)),
            TextColumn::make('max_income')->label(__('Max'))->formatStateUsing(fn (?int $state) => $state ? '৳'.number_format($state / 100, 2) : __('Unlimited')),
            TextColumn::make('tax_rate')->label(__('Rate'))->formatStateUsing(fn (float $state) => $state.'%'),
            TextColumn::make('description')->label(__('Description')),
        ])->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomeTaxSlabs::route('/'),
            'create' => Pages\CreateIncomeTaxSlab::route('/create'),
            'edit' => Pages\EditIncomeTaxSlab::route('/{record}/edit'),
        ];
    }
}
