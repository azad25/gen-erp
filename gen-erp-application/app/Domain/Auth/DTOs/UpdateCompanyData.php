<?php

namespace App\Domain\Auth\DTOs;

readonly class UpdateCompanyData
{
    public function __construct(
        public ?string $name = null,
        public ?string $address = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $vatBin = null,
        public ?string $businessType = null,
        public ?array $settings = null,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name') : null,
            address: $request->has('address') ? $request->string('address') : null,
            phone: $request->has('phone') ? $request->string('phone') : null,
            email: $request->has('email') ? $request->string('email') : null,
            vatBin: $request->has('vat_bin') ? $request->string('vat_bin') : null,
            businessType: $request->has('business_type') ? $request->string('business_type') : null,
            settings: $request->has('settings') ? $request->array('settings') : null,
        );
    }

    /**
     * Convert to array for model update (only non-null values).
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->address !== null) {
            $data['address'] = $this->address;
        }

        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        if ($this->vatBin !== null) {
            $data['vat_bin'] = $this->vatBin;
        }

        if ($this->businessType !== null) {
            $data['business_type'] = $this->businessType;
        }

        if ($this->settings !== null) {
            $data['settings'] = $this->settings;
        }

        return $data;
    }
}