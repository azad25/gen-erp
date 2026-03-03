<?php

namespace App\Domain\Customer\DTOs;

readonly class CreateCustomerData
{
    public function __construct(
        public int $companyId,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $district,
        public ?int $creditLimit,
        public ?int $creditDays,
        public ?int $openingBalance,
        public string $status,
        public ?int $contactGroupId,
        public array $customFields,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\App\Http\Requests\Api\V1\StoreCustomerRequest|\App\Http\Requests\Api\V1\UpdateCustomerRequest $request): self
    {
        $user = $request->user();
        $company = activeCompany();

        return new self(
            companyId: $company->id,
            name: $request->string('name'),
            email: $request->string('email'),
            phone: $request->string('phone'),
            address: $request->string('address'),
            district: $request->string('district'),
            creditLimit: $request->integer('credit_limit'),
            creditDays: $request->integer('credit_days'),
            openingBalance: $request->integer('opening_balance'),
            status: $request->string('status', 'active'),
            contactGroupId: $request->integer('contact_group_id'),
            customFields: $request->array('custom_fields'),
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'district' => $this->district,
            'credit_limit' => $this->creditLimit,
            'credit_days' => $this->creditDays,
            'opening_balance' => $this->openingBalance,
            'status' => $this->status,
            'contact_group_id' => $this->contactGroupId,
        ];
    }
}