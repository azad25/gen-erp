<?php

namespace App\Domain\CRM\DTOs;

class OpportunityData
{
    public function __construct(
        public readonly string $name,
        public readonly float $amount,
        public readonly int $pipelineId,
        public readonly int $stageId,
        public readonly ?\DateTime $expectedCloseDate = null,
        public readonly ?string $description = null,
        public readonly string $currency = 'BDT',
        public readonly ?int $probability = null,
        public readonly ?int $leadId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $assignedTo = null,
        public readonly ?string $source = null,
        public readonly ?string $campaign = null,
        public readonly ?array $products = null,
        public readonly float $discountAmount = 0,
        public readonly float $taxAmount = 0,
        public readonly ?array $customFields = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            amount: (float) $data['amount'],
            pipelineId: (int) $data['pipeline_id'],
            stageId: (int) $data['stage_id'],
            expectedCloseDate: isset($data['expected_close_date']) ? new \DateTime($data['expected_close_date']) : null,
            description: $data['description'] ?? null,
            currency: $data['currency'] ?? 'BDT',
            probability: isset($data['probability']) ? (int) $data['probability'] : null,
            leadId: isset($data['lead_id']) ? (int) $data['lead_id'] : null,
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            assignedTo: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            source: $data['source'] ?? null,
            campaign: $data['campaign'] ?? null,
            products: $data['products'] ?? null,
            discountAmount: isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0,
            taxAmount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : 0,
            customFields: $data['custom_fields'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public static function fromArrayForUpdate(array $data, $existingOpportunity): self
    {
        return new self(
            name: $data['name'] ?? $existingOpportunity->name,
            amount: isset($data['amount']) ? (float) $data['amount'] : $existingOpportunity->amount,
            pipelineId: isset($data['pipeline_id']) ? (int) $data['pipeline_id'] : $existingOpportunity->pipeline_id,
            stageId: isset($data['stage_id']) ? (int) $data['stage_id'] : $existingOpportunity->stage_id,
            expectedCloseDate: isset($data['expected_close_date']) ? new \DateTime($data['expected_close_date']) : $existingOpportunity->expected_close_date,
            description: array_key_exists('description', $data) ? $data['description'] : $existingOpportunity->description,
            currency: $data['currency'] ?? $existingOpportunity->currency,
            probability: array_key_exists('probability', $data) ? (isset($data['probability']) ? (int) $data['probability'] : null) : $existingOpportunity->probability,
            leadId: array_key_exists('lead_id', $data) ? (isset($data['lead_id']) ? (int) $data['lead_id'] : null) : $existingOpportunity->lead_id,
            customerId: array_key_exists('customer_id', $data) ? (isset($data['customer_id']) ? (int) $data['customer_id'] : null) : $existingOpportunity->customer_id,
            assignedTo: array_key_exists('assigned_to', $data) ? (isset($data['assigned_to']) ? (int) $data['assigned_to'] : null) : $existingOpportunity->assigned_to,
            source: array_key_exists('source', $data) ? $data['source'] : $existingOpportunity->source,
            campaign: array_key_exists('campaign', $data) ? $data['campaign'] : $existingOpportunity->campaign,
            products: array_key_exists('products', $data) ? $data['products'] : $existingOpportunity->products,
            discountAmount: isset($data['discount_amount']) ? (float) $data['discount_amount'] : $existingOpportunity->discount_amount,
            taxAmount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : $existingOpportunity->tax_amount,
            customFields: array_key_exists('custom_fields', $data) ? $data['custom_fields'] : $existingOpportunity->custom_fields,
            notes: array_key_exists('notes', $data) ? $data['notes'] : $existingOpportunity->notes,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'amount' => $this->amount,
            'pipeline_id' => $this->pipelineId,
            'stage_id' => $this->stageId,
            'expected_close_date' => $this->expectedCloseDate?->format('Y-m-d'),
            'description' => $this->description,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'lead_id' => $this->leadId,
            'customer_id' => $this->customerId,
            'assigned_to' => $this->assignedTo,
            'source' => $this->source,
            'campaign' => $this->campaign,
            'products' => $this->products,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->getTotalAmount(),
            'custom_fields' => $this->customFields,
            'notes' => $this->notes,
        ];
    }

    public function getTotalAmount(): float
    {
        return $this->amount + $this->taxAmount - $this->discountAmount;
    }

    public function getWeightedValue(): float
    {
        $probability = $this->probability ?? 0;
        return round($this->amount * ($probability / 100), 2);
    }
}