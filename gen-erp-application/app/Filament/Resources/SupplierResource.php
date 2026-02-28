<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\ContactGroup;
use App\Models\Supplier;
use App\Services\CustomFieldService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends BaseResource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __entity('supplier', plural: true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Supplier')
                ->tabs([
                    Tabs\Tab::make(__('Contact Info'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('Supplier Name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('supplier_code')
                                ->label(__('Supplier Code'))
                                ->maxLength(50)
                                ->helperText(__('Auto-generated if left blank')),
                            TextInput::make('contact_person')
                                ->label(__('Contact Person'))
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('Email'))
                                ->email()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label(__('Phone'))
                                ->tel()
                                ->maxLength(20),
                            TextInput::make('mobile')
                                ->label(__('Mobile'))
                                ->tel()
                                ->maxLength(20)
                                ->rule('regex:/^01[3-9]\d{8}$/'),
                            Select::make('group_id')
                                ->label(__('Group'))
                                ->options(
                                    ContactGroup::withoutGlobalScopes()
                                        ->where('company_id', activeCompany()->id)
                                        ->whereIn('type', ['supplier', 'both'])
                                        ->pluck('name', 'id')
                                )
                                ->nullable()
                                ->searchable(),
                            TextInput::make('vat_bin')
                                ->label(__('VAT BIN'))
                                ->maxLength(20),
                            Textarea::make('notes')
                                ->label(__('Notes'))
                                ->maxLength(2000),
                            Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('Address'))
                        ->schema([
                            TextInput::make('address_line1')->label(__('Address Line 1'))->maxLength(255),
                            TextInput::make('address_line2')->label(__('Address Line 2'))->maxLength(255),
                            TextInput::make('city')->label(__('City'))->maxLength(100),
                            TextInput::make('district')->label(__('District'))->maxLength(100),
                            TextInput::make('postal_code')->label(__('Postal Code'))->maxLength(20),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('Financial'))
                        ->schema([
                            TextInput::make('tds_rate')
                                ->label(__('TDS Rate (%)'))
                                ->numeric()
                                ->suffix('%')
                                ->default(0)
                                ->helperText(__('Tax Deducted at Source — deducted on supplier payments')),
                            TextInput::make('vds_rate')
                                ->label(__('VDS Rate (%)'))
                                ->numeric()
                                ->suffix('%')
                                ->default(0)
                                ->helperText(__('VAT Deducted at Source')),
                            TextInput::make('credit_days')
                                ->label(__('Credit Days'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            TextInput::make('opening_balance')
                                ->label(__('Opening Balance (৳)'))
                                ->numeric()
                                ->prefix('৳')
                                ->default(0)
                                ->helperText(__('Positive = you owe the supplier')),
                            DatePicker::make('opening_balance_date')
                                ->label(__('Opening Balance Date')),
                            Section::make(__('Bank Details'))
                                ->schema([
                                    TextInput::make('bank_name')->label(__('Bank Name'))->maxLength(255),
                                    TextInput::make('bank_account_number')->label(__('Account Number'))->maxLength(100),
                                    TextInput::make('bank_routing_number')->label(__('Routing Number'))->maxLength(50),
                                ])
                                ->columns(3),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('Custom Fields'))
                        ->schema(function (): array {
                            $service = app(CustomFieldService::class);
                            $components = $service->buildFormComponents('supplier');

                            if (empty($components)) {
                                return [
                                    Placeholder::make('no_custom_fields')
                                        ->label('')
                                        ->content(__('No custom fields configured.')),
                                ];
                            }

                            return $components;
                        }),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('supplier_code')->label(__('Code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('contact_person')->label(__('Contact'))->placeholder('—'),
                TextColumn::make('phone')->label(__('Phone'))->searchable(),
                TextColumn::make('tds_rate')
                    ->label(__('TDS %'))
                    ->formatStateUsing(fn (float $state): string => $state > 0 ? "{$state}%" : '—'),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->defaultSort('name')
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
