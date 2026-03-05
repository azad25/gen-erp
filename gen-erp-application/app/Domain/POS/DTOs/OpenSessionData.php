<?php

namespace App\Domain\POS\DTOs;

readonly class OpenSessionData
{
    public function __construct(
        public int $companyId,
        public int $branchId,
        public int $openedBy,
        public int $openingCash,
        public ?string $notes = null,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'opened_by' => $this->openedBy,
            'opening_cash' => $this->openingCash,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $this->notes,
        ];
    }
}
