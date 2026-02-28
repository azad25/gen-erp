<?php

namespace App\Filament\Pages;

use App\Services\AuditLogger;
use App\Services\CompanyContext;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Company settings page with 4 tabs: General, Location, Financial, Preferences.
 */
class CompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Company';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $company = CompanyContext::active();

        $this->form->fill([
            'name' => $company->name,
            'slug' => $company->slug,
            'business_type' => $company->business_type?->value,
            'logo' => $company->logo_url,
            'phone' => $company->phone,
            'email' => $company->email,
            'website' => $company->website,
            'address_line1' => $company->address_line1,
            'address_line2' => $company->address_line2,
            'city' => $company->city,
            'district' => $company->district,
            'postal_code' => $company->postal_code,
            'vat_registered' => $company->vat_registered,
            'vat_bin' => $company->vat_bin,
            'date_format' => $company->settings['date_format'] ?? 'd M Y',
            'time_format' => $company->settings['time_format'] ?? 'h:i A',
            'simplified_mode' => $company->settings['simplified_mode'] ?? false,
            'invoice_prefix' => $company->settings['invoice_prefix'] ?? 'INV',
            'po_prefix' => $company->settings['po_prefix'] ?? 'PO',
            'fiscal_year_start' => $company->settings['fiscal_year_start'] ?? '07-01',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('settings')
                    ->tabs([
                        $this->generalTab(),
                        $this->locationTab(),
                        $this->financialTab(),
                        $this->preferencesTab(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $company = CompanyContext::active();
        $oldValues = $company->toArray();

        $company->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'website' => $data['website'],
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'],
            'city' => $data['city'],
            'district' => $data['district'],
            'postal_code' => $data['postal_code'],
            'vat_registered' => $data['vat_registered'],
            'vat_bin' => $data['vat_registered'] ? $data['vat_bin'] : null,
            'settings' => [
                'date_format' => $data['date_format'],
                'time_format' => $data['time_format'],
                'simplified_mode' => $data['simplified_mode'],
                'invoice_prefix' => $data['invoice_prefix'],
                'po_prefix' => $data['po_prefix'],
                'fiscal_year_start' => $data['fiscal_year_start'],
            ],
        ]);

        // Audit log the settings change
        app(AuditLogger::class)->log('settings_updated', $company, $oldValues, $company->fresh()->toArray());

        Notification::make()
            ->title(__('Company settings saved.'))
            ->success()
            ->send();
    }

    private function generalTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('General'))
            ->icon('heroicon-o-building-office')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label(__('Company Name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(__('Auto-generated from name. You may edit it.')),
                    Placeholder::make('business_type_display')
                        ->label(__('Business Type'))
                        ->content(fn () => CompanyContext::active()->business_type?->label() ?? '-')
                        ->helperText(__('Contact support to change your business type.')),
                    TextInput::make('phone')
                        ->label(__('Phone'))
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->maxLength(255),
                    TextInput::make('website')
                        ->label(__('Website'))
                        ->url()
                        ->maxLength(500),
                ]),
            ]);
    }

    private function locationTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('Location'))
            ->icon('heroicon-o-map-pin')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('address_line1')
                        ->label(__('Address Line 1'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('address_line2')
                        ->label(__('Address Line 2'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('city')
                        ->label(__('City'))
                        ->maxLength(100),
                    Select::make('district')
                        ->label(__('District'))
                        ->searchable()
                        ->options(array_combine(
                            $this->getDistricts(),
                            $this->getDistricts(),
                        )),
                    TextInput::make('postal_code')
                        ->label(__('Postal Code'))
                        ->maxLength(20),
                ]),
            ]);
    }

    private function financialTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('Financial'))
            ->icon('heroicon-o-banknotes')
            ->schema([
                Grid::make(2)->schema([
                    Toggle::make('vat_registered')
                        ->label(__('VAT Registered'))
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('vat_bin')
                        ->label(__('VAT BIN'))
                        ->maxLength(20)
                        ->visible(fn ($get): bool => (bool) $get('vat_registered')),
                    Placeholder::make('currency_display')
                        ->label(__('Currency'))
                        ->content('BDT (৳) — Bangladeshi Taka')
                        ->helperText(__('Currency cannot be changed in the current version.')),
                    Select::make('fiscal_year_start')
                        ->label(__('Fiscal Year Start'))
                        ->options([
                            '07-01' => __('July 1'),
                            '01-01' => __('January 1'),
                            '04-01' => __('April 1'),
                        ]),
                ]),
            ]);
    }

    private function preferencesTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('Preferences'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('date_format')
                        ->label(__('Date Format'))
                        ->options([
                            'd M Y' => __('01 Jan 2026'),
                            'd/m/Y' => __('01/01/2026'),
                            'Y-m-d' => __('2026-01-01'),
                        ]),
                    Select::make('time_format')
                        ->label(__('Time Format'))
                        ->options([
                            'h:i A' => __('12-hour (02:30 PM)'),
                            'H:i' => __('24-hour (14:30)'),
                        ]),
                    Toggle::make('simplified_mode')
                        ->label(__('Simplified Mode'))
                        ->helperText(__('Hides advanced features for solo businesses.'))
                        ->columnSpanFull(),
                    TextInput::make('invoice_prefix')
                        ->label(__('Invoice Prefix'))
                        ->maxLength(20)
                        ->default('INV'),
                    TextInput::make('po_prefix')
                        ->label(__('PO Prefix'))
                        ->maxLength(20)
                        ->default('PO'),
                ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function getDistricts(): array
    {
        return [
            'Bagerhat', 'Bandarban', 'Barguna', 'Barisal', 'Bhola',
            'Bogra', 'Brahmanbaria', 'Chandpur', 'Chapainawabganj', 'Chittagong',
            'Chuadanga', 'Comilla', 'Cox\'s Bazar', 'Dhaka', 'Dinajpur',
            'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj',
            'Habiganj', 'Jamalpur', 'Jessore', 'Jhalokati', 'Jhenaidah',
            'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram',
            'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura',
            'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh',
            'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore',
            'Nawabganj', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna',
            'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi',
            'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur',
            'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail',
        ];
    }
}
