<?php

namespace App\Domain\Auth\DataTransferObjects;

/**
 * Data Transfer Object for user registration.
 */
readonly class UserRegistrationData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
        public ?string $companyName = null,
    ) {}

    /**
     * Create from array (typically from request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'] ?? null,
            companyName: $data['company_name'] ?? null,
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone,
            'company_name' => $this->companyName,
        ];
    }
}
