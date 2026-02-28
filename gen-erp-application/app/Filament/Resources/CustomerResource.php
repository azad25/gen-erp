<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\ContactGroup;
use App\Models\Customer;
use App\Services\CustomFieldService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerResource extends BaseResource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __entity('customer', plural: true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Customer')
                ->tabs([
                    Tabs\Tab::make(__('Contact Info'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('Name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('customer_code')
                                ->label(__('Customer Code'))
                                ->maxLength(50)
                                ->helperText(__('Auto-generated if left blank')),
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
                                        ->whereIn('type', ['customer', 'both'])
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
                            Select::make('district')
                                ->label(__('District'))
                                ->options(self::bdDistricts())
                                ->searchable(),
                            TextInput::make('postal_code')->label(__('Postal Code'))->maxLength(20),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('Financial'))
                        ->schema([
                            TextInput::make('credit_limit')
                                ->label(__('Credit Limit (৳)'))
                                ->numeric()
                                ->prefix('৳')
                                ->default(0),
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
                                ->helperText(__('Positive = customer owes you')),
                            DatePicker::make('opening_balance_date')
                                ->label(__('Opening Balance Date')),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('Custom Fields'))
                        ->schema(function (): array {
                            $service = app(CustomFieldService::class);
                            $components = $service->buildFormComponents('customer');

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
                TextColumn::make('customer_code')->label(__('Code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('phone')->label(__('Phone'))->searchable(),
                TextColumn::make('city')->label(__('City'))->sortable()->placeholder('—'),
                TextColumn::make('opening_balance')
                    ->label(__('Balance'))
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format(abs($state) / 100, 2))
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->label(__('Credit Limit'))
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '৳'.number_format($state / 100, 2) : '—'),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('group_id')
                    ->label(__('Group'))
                    ->options(
                        ContactGroup::withoutGlobalScopes()
                            ->whereIn('type', ['customer', 'both'])
                            ->pluck('name', 'id')
                    ),
            ])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function bdDistricts(): array
    {
        $districts = [
            'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura',
            'Brahmanbaria', 'Chandpur', 'Chapainawabganj', 'Chattogram', 'Chuadanga',
            'Comilla', "Cox's Bazar", 'Dhaka', 'Dinajpur', 'Faridpur', 'Feni',
            'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore',
            'Jhalokathi', 'Jhenaidah', 'Joypurhat', 'Khagrachari', 'Khulna', 'Kishoreganj',
            'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura',
            'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon',
            'Narail', 'Narayanganj', 'Narsingdi', 'Natore', 'Nawabganj', 'Netrokona',
            'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur',
            'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur',
            'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon',
        ];

        return array_combine($districts, $districts);
    }
}
