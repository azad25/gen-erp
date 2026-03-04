<?php

namespace App\Domain\CMS\DTOs;

/**
 * Data Transfer Object for contact form submissions.
 */
readonly class ContactSubmissionData
{
    public function __construct(
        public int $siteId,
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $company = null,
        public ?string $subject = null,
        public string $message = '',
        public ?array $formData = null,
        public string $source = 'contact_form',
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            siteId: $data['site_id'],
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            company: $data['company'] ?? null,
            subject: $data['subject'] ?? null,
            message: $data['message'] ?? '',
            formData: $data['form_data'] ?? null,
            source: $data['source'] ?? 'contact_form',
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'site_id' => $this->siteId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'subject' => $this->subject,
            'message' => $this->message,
            'form_data' => $this->formData,
            'source' => $this->source,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}