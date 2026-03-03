<?php

namespace App\Domain\Shared\Commands;

/**
 * Base interface for all command objects in CQRS pattern.
 * Commands represent write operations that change system state.
 */
interface CommandInterface
{
    /**
     * Get the unique identifier for this command.
     */
    public function getCommandId(): string;

    /**
     * Get the timestamp when this command was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get the user who initiated this command.
     */
    public function getInitiatedBy(): ?int;
}