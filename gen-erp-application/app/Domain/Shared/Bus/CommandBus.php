<?php

namespace App\Domain\Shared\Bus;

use App\Domain\Shared\Commands\CommandInterface;
use App\Domain\Shared\Commands\CommandHandlerInterface;
use Illuminate\Container\Container;
use InvalidArgumentException;

/**
 * Command Bus implementation for CQRS pattern.
 * Routes commands to their appropriate handlers.
 */
class CommandBus
{
    private array $handlers = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Register a command handler.
     */
    public function register(string $commandClass, string $handlerClass): void
    {
        $this->handlers[$commandClass] = $handlerClass;
    }

    /**
     * Execute a command through its handler.
     */
    public function execute(CommandInterface $command): mixed
    {
        $commandClass = get_class($command);
        
        if (!isset($this->handlers[$commandClass])) {
            throw new InvalidArgumentException("No handler registered for command: {$commandClass}");
        }

        $handlerClass = $this->handlers[$commandClass];
        $handler = $this->container->make($handlerClass);

        if (!$handler instanceof CommandHandlerInterface) {
            throw new InvalidArgumentException("Handler {$handlerClass} must implement CommandHandlerInterface");
        }

        return $handler->handle($command);
    }

    /**
     * Get all registered handlers.
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}