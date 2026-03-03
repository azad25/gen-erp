<?php

namespace App\Domain\Accounting\DTOs;

use App\Support\Enums\AccountType;

/**
 * Data Transfer Object for updating an account.
 */
readonly class UpdateAccountData
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?AccountType $account_type = null,
        public ?int $opening_balance = null,
        public ?string $description = null,
        public ?bool $is_active = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
            'account_type' => $this->account_type,
            'opening_balance' => $this->opening_balance,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ], fn($value) => $value !== null);
    }
}