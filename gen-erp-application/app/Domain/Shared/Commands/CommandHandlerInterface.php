<?php

namespace App\Domain\Shared\Commands;

/**
 * Interface for all command handlers in CQRS pattern.
 * Command handlers execute write operations that change system state.
 */
interface CommandHandlerInterface
{
    /**
     * Handle the command and return the result.
     *
     * @param CommandInterface $command
     * @return mixed
     */
    public function handle(CommandInterface $command): mixed;
}