<?php

namespace App\Domain\Shared\Commands;

use Illuminate\Support\Str;

/**
 * Base implementation for all commands in CQRS pattern.
 */
abstract class BaseCommand implements CommandInterface
{
    private readonly string $commandId;
    private readonly \DateTimeImmutable $createdAt;
    private readonly ?int $initiatedBy;

    public function __construct(?int $initiatedBy = null)
    {
        $this->commandId = Str::uuid()->toString();
        $this->createdAt = new \DateTimeImmutable();
        $this->initiatedBy = $initiatedBy ?? auth()->id();
    }

    public function getCommandId(): string
    {
        return $this->commandId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getInitiatedBy(): ?int
    {
        return $this->initiatedBy;
    }
}