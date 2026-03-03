<?php

namespace App\Domain\Auth\DataTransferObjects;

/**
 * Data Transfer Object for company setup.
 */
readonly class CompanySetupData
{
    public function __construct(
        public string $name,
        public string $businessType,
        public string $country = 'BD',
        public string $currency = 'BDT',
        public string $timezone = 'Asia/Dhaka',
        public string $locale = 'en',
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $postalCode = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $website = null,
        public ?string $vatBin = null,
        public ?string $tradeLicense = null,
        public ?string $tin = null,
        public string $plan = 'free',
    ) {}

    /**
     * Create from array (typically from request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            businessType: $data['business_type'],
            country: $data['country'] ?? 'BD',
            currency: $data['currency'] ?? 'BDT',
            timezone: $data['timezone'] ?? 'Asia/Dhaka',
            locale: $data['locale'] ?? 'en',
            addressLine1: $data['address_line1'] ?? null,
            addressLine2: $data['address_line2'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            website: $data['website'] ?? null,
            vatBin: $data['vat_bin'] ?? null,
            tradeLicense: $data['trade_license'] ?? null,
            tin: $data['tin'] ?? null,
            plan: $data['plan'] ?? 'free',
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'business_type' => $this->businessType,
            'country' => $this->country,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'city' => $this->city,
            'district' => $this->district,
            'postal_code' => $this->postalCode,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'vat_bin' => $this->vatBin,
            'trade_license' => $this->tradeLicense,
            'tin' => $this->tin,
            'plan' => $this->plan,
            'vat_registered' => ! empty($this->vatBin),
            'is_active' => true,
        ];
    }

    /**
     * Get slug from company name.
     */
    public function getSlug(): string
    {
        return \Illuminate\Support\Str::slug($this->name);
    }

    /**
     * Get UUID for the company.
     */
    public function getUuid(): string
    {
        return \Illuminate\Support\Str::uuid()->toString();
    }
}
