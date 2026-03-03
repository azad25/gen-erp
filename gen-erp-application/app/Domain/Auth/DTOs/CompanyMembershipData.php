<?php

namespace App\Domain\Auth\DTOs;

readonly class CompanyMembershipData
{
    public function __construct(
        public int $companyId,
        public string $role,
        public bool $isOwner = false,
        public bool $isActive = true,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            companyId: $request->integer('company_id'),
            role: $request->string('role'),
            isOwner: $request->boolean('is_owner', false),
            isActive: $request->boolean('is_active', true),
        );
    }

    /**
     * Convert to array for pivot table.
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'is_owner' => $this->isOwner,
            'is_active' => $this->isActive,
            'joined_at' => now(),
        ];
    }
}