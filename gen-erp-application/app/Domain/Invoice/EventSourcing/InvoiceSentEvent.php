<?php

namespace App\Domain\Invoice\EventSourcing;

use App\Domain\Shared\EventSourcing\DomainEvent;

/**
 * Event sourcing event for invoice sending.
 */
class InvoiceSentEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly \DateTimeImmutable $sentAt,
        public readonly ?int $sentBy,
        int $version = 1
    ) {
        parent::__construct($aggregateId, 'invoice', $version);
    }

    public function getEventData(): array
    {
        return [
            'sent_at' => $this->sentAt->format('Y-m-d H:i:s'),
            'sent_by' => $this->sentBy,
        ];
    }

    public static function fromEventData(string $aggregateId, string $aggregateType, array $data, int $version): static
    {
        return new static(
            $aggregateId,
            new \DateTimeImmutable($data['sent_at']),
            $data['sent_by'],
            $version
        );
    }
}