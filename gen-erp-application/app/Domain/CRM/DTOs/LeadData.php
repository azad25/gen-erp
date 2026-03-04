<?php

namespace App\Domain\CRM\DTOs;

use App\Domain\CRM\Enums\LeadSource;
use App\Domain\CRM\Enums\LeadStatus;

class LeadData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $companyName = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly string $country = 'BD',
        public readonly ?string $postalCode = null,
        public readonly LeadStatus $status = LeadStatus::NEW,
        public readonly ?LeadSource $source = null,
        public readonly int $score = 0,
        public readonly ?float $estimatedValue = null,
        public readonly string $currency = 'BDT',
        public readonly ?\DateTime $expectedCloseDate = null,
        public readonly ?int $assignedTo = null,
        public readonly ?array $customFields = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            companyName: $data['company_name'] ?? null,
            jobTitle: $data['job_title'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? 'BD',
            postalCode: $data['postal_code'] ?? null,
            status: isset($data['status']) ? LeadStatus::from($data['status']) : LeadStatus::NEW,
            source: isset($data['source']) ? LeadSource::from($data['source']) : null,
            score: $data['score'] ?? 0,
            estimatedValue: isset($data['estimated_value']) ? (float) $data['estimated_value'] : null,
            currency: $data['currency'] ?? 'BDT',
            expectedCloseDate: isset($data['expected_close_date']) ? new \DateTime($data['expected_close_date']) : null,
            assignedTo: $data['assigned_to'] ?? null,
            customFields: $data['custom_fields'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_name' => $this->companyName,
            'job_title' => $this->jobTitle,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'status' => $this->status->value,
            'source' => $this->source?->value,
            'score' => $this->score,
            'estimated_value' => $this->estimatedValue,
            'currency' => $this->currency,
            'expected_close_date' => $this->expectedCloseDate?->format('Y-m-d'),
            'assigned_to' => $this->assignedTo,
            'custom_fields' => $this->customFields,
            'notes' => $this->notes,
        ];
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}