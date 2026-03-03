<?php

namespace App\Domain\Shared\EventSourcing;

use Illuminate\Support\Str;

/**
 * Base class for all domain events in event sourcing.
 */
abstract class DomainEvent
{
    private readonly string $eventId;
    private readonly \DateTimeImmutable $occurredAt;
    private readonly int $version;

    public function __construct(
        public readonly string $aggregateId,
        public readonly string $aggregateType,
        int $version = 1
    ) {
        $this->eventId = Str::uuid()->toString();
        $this->occurredAt = new \DateTimeImmutable();
        $this->version = $version;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getEventType(): string
    {
        return static::class;
    }

    /**
     * Get the event data for serialization.
     */
    abstract public function getEventData(): array;

    /**
     * Create event from stored data.
     */
    abstract public static function fromEventData(string $aggregateId, string $aggregateType, array $data, int $version): static;
}