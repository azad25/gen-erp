<?php

namespace App\Domain\CMS\DTOs;

readonly class CreateCustomerData
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $phone = null,
        public ?string $password = null,
        public bool $isGuest = false,
    ) {}

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'password' => $this->password,
            'is_guest' => $this->isGuest,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            firstName: $data['first_name'] ?? $data['firstName'] ?? '',
            lastName: $data['last_name'] ?? $data['lastName'] ?? '',
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
            isGuest: $data['is_guest'] ?? $data['isGuest'] ?? false,
        );
    }
}