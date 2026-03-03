<?php

namespace App\Domain\Shared\Queries;

use Illuminate\Support\Str;

/**
 * Base implementation for all queries in CQRS pattern.
 */
abstract class BaseQuery implements QueryInterface
{
    private readonly string $queryId;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->queryId = Str::uuid()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}