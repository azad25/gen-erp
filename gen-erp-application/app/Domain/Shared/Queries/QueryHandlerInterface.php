<?php

namespace App\Domain\Shared\Queries;

/**
 * Interface for all query handlers in CQRS pattern.
 * Query handlers execute read operations that don't change system state.
 */
interface QueryHandlerInterface
{
    /**
     * Handle the query and return the result.
     *
     * @param QueryInterface $query
     * @return mixed
     */
    public function handle(QueryInterface $query): mixed;
}