<?php

namespace App\Domain\Shared\Bus;

use App\Domain\Shared\Queries\QueryInterface;
use App\Domain\Shared\Queries\QueryHandlerInterface;
use Illuminate\Container\Container;
use InvalidArgumentException;

/**
 * Query Bus implementation for CQRS pattern.
 * Routes queries to their appropriate handlers.
 */
class QueryBus
{
    private array $handlers = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Register a query handler.
     */
    public function register(string $queryClass, string $handlerClass): void
    {
        $this->handlers[$queryClass] = $handlerClass;
    }

    /**
     * Execute a query through its handler.
     */
    public function execute(QueryInterface $query): mixed
    {
        $queryClass = get_class($query);
        
        if (!isset($this->handlers[$queryClass])) {
            throw new InvalidArgumentException("No handler registered for query: {$queryClass}");
        }

        $handlerClass = $this->handlers[$queryClass];
        $handler = $this->container->make($handlerClass);

        if (!$handler instanceof QueryHandlerInterface) {
            throw new InvalidArgumentException("Handler {$handlerClass} must implement QueryHandlerInterface");
        }

        return $handler->handle($query);
    }

    /**
     * Get all registered handlers.
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}