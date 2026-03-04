<?php

namespace App\Domain\Logistics\DTOs;

use App\Domain\Logistics\Enums\ReturnReason;

class ReturnRequestData
{
    public function __construct(
        public readonly int $shipmentId,
        public readonly ReturnReason $reason,
        public readonly string $reasonDetails,
        public readonly int $requestedBy,
        public readonly ?array $images = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            shipmentId: $data['shipment_id'],
            reason: ReturnReason::from($data['reason']),
            reasonDetails: $data['reason_details'],
            requestedBy: $data['requested_by'],
            images: $data['images'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'shipment_id' => $this->shipmentId,
            'reason' => $this->reason->value,
            'reason_details' => $this->reasonDetails,
            'requested_by' => $this->requestedBy,
            'images' => $this->images,
        ];
    }
}