<?php

namespace App\Domain\Accounting\DTOs;

use App\Support\Enums\AccountType;

/**
 * Data Transfer Object for creating a new account.
 */
readonly class CreateAccountData
{
    public function __construct(
        public int $company_id,
        public int $account_group_id,
        public string $code,
        public string $name,
        public AccountType $account_type,
        public ?int $opening_balance = null,
        public ?string $description = null,
        public bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'account_group_id' => $this->account_group_id,
            'code' => $this->code,
            'name' => $this->name,
            'account_type' => $this->account_type,
            'opening_balance' => $this->opening_balance,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}