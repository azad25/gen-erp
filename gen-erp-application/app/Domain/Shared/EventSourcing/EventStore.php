<?php

namespace App\Domain\Shared\EventSourcing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Event Store implementation for event sourcing.
 */
class EventStore
{
    private const TABLE = 'event_store';

    /**
     * Store a domain event.
     */
    public function store(DomainEvent $event): void
    {
        DB::table(self::TABLE)->insert([
            'event_id' => $event->getEventId(),
            'aggregate_id' => $event->aggregateId,
            'aggregate_type' => $event->aggregateType,
            'event_type' => $event->getEventType(),
            'event_data' => json_encode($event->getEventData()),
            'version' => $event->getVersion(),
            'occurred_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
            'created_at' => now(),
        ]);
    }

    /**
     * Get all events for an aggregate.
     */
    public function getEventsForAggregate(string $aggregateId, string $aggregateType): Collection
    {
        $events = DB::table(self::TABLE)
            ->where('aggregate_id', $aggregateId)
            ->where('aggregate_type', $aggregateType)
            ->orderBy('version')
            ->get();

        return $events->map(function ($eventData) {
            $eventClass = $eventData->event_type;
            $data = json_decode($eventData->event_data, true);

            return $eventClass::fromEventData(
                $eventData->aggregate_id,
                $eventData->aggregate_type,
                $data,
                $eventData->version
            );
        });
    }

    /**
     * Get events for an aggregate from a specific version.
     */
    public function getEventsForAggregateFromVersion(string $aggregateId, string $aggregateType, int $fromVersion): Collection
    {
        $events = DB::table(self::TABLE)
            ->where('aggregate_id', $aggregateId)
            ->where('aggregate_type', $aggregateType)
            ->where('version', '>', $fromVersion)
            ->orderBy('version')
            ->get();

        return $events->map(function ($eventData) {
            $eventClass = $eventData->event_type;
            $data = json_decode($eventData->event_data, true);

            return $eventClass::fromEventData(
                $eventData->aggregate_id,
                $eventData->aggregate_type,
                $data,
                $eventData->version
            );
        });
    }

    /**
     * Get the latest version for an aggregate.
     */
    public function getLatestVersion(string $aggregateId, string $aggregateType): int
    {
        return DB::table(self::TABLE)
            ->where('aggregate_id', $aggregateId)
            ->where('aggregate_type', $aggregateType)
            ->max('version') ?? 0;
    }
}