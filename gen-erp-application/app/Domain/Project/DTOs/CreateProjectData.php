<?php

namespace App\Domain\Project\DTOs;

class CreateProjectData
{
    public function __construct(
        public int $companyId,
        public string $name,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $priority = null,
        public ?\DateTime $startDate = null,
        public ?\DateTime $endDate = null,
        public ?float $budget = null,
        public ?string $currency = null,
        public ?string $clientName = null,
        public ?string $clientEmail = null,
        public ?string $clientPhone = null,
        public ?int $projectManagerId = null,
        public ?bool $isBillable = null,
        public ?float $hourlyRate = null,
        public ?float $estimatedHours = null,
        public ?string $color = null,
        public ?array $settings = null,
        public ?array $memberIds = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null,
            startDate: isset($data['start_date']) ? new \DateTime($data['start_date']) : null,
            endDate: isset($data['end_date']) ? new \DateTime($data['end_date']) : null,
            budget: $data['budget'] ?? null,
            currency: $data['currency'] ?? null,
            clientName: $data['client_name'] ?? null,
            clientEmail: $data['client_email'] ?? null,
            clientPhone: $data['client_phone'] ?? null,
            projectManagerId: $data['project_manager_id'] ?? null,
            isBillable: $data['is_billable'] ?? null,
            hourlyRate: $data['hourly_rate'] ?? null,
            estimatedHours: $data['estimated_hours'] ?? null,
            color: $data['color'] ?? null,
            settings: $data['settings'] ?? null,
            memberIds: $data['member_ids'] ?? null,
        );
    }
}