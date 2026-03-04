<?php

namespace App\Domain\CMS\DTOs;

readonly class UpdateCustomerData
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phone = null,
        public ?string $password = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'password' => $this->password,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? $data['firstName'] ?? null,
            lastName: $data['last_name'] ?? $data['lastName'] ?? null,
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
        );
    }
}