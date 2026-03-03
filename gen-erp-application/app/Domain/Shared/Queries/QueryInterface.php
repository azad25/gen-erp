<?php

namespace App\Domain\Shared\Queries;

/**
 * Base interface for all query objects in CQRS pattern.
 * Queries represent read operations that don't change system state.
 */
interface QueryInterface
{
    /**
     * Get the unique identifier for this query.
     */
    public function getQueryId(): string;

    /**
     * Get the timestamp when this query was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;
}