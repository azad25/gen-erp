<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Shared\Bus\CommandBus;
use App\Domain\Shared\Bus\QueryBus;
use App\Domain\Shared\EventSourcing\EventStore;
use App\Domain\Shared\Cache\CacheService;
use App\Domain\Shared\Authorization\ResourcePermissionChecker;

// Invoice Commands and Handlers
use App\Domain\Invoice\Commands\CreateInvoiceCommand;
use App\Domain\Invoice\Commands\SendInvoiceCommand;
use App\Domain\Invoice\Handlers\CreateInvoiceCommandHandler;
use App\Domain\Invoice\Handlers\SendInvoiceCommandHandler;

// Invoice Queries and Handlers
use App\Domain\Invoice\Queries\GetInvoiceQuery;
use App\Domain\Invoice\Queries\GetInvoicesQuery;
use App\Domain\Invoice\Handlers\GetInvoiceQueryHandler;
use App\Domain\Invoice\Handlers\GetInvoicesQueryHandler;

/**
 * Service provider for CQRS pattern implementation.
 */
class CqrsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register core CQRS services
        $this->app->singleton(CommandBus::class);
        $this->app->singleton(QueryBus::class);
        $this->app->singleton(EventStore::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(ResourcePermissionChecker::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerCommandHandlers();
        $this->registerQueryHandlers();
    }

    /**
     * Register command handlers with the command bus.
     */
    private function registerCommandHandlers(): void
    {
        $commandBus = $this->app->make(CommandBus::class);

        // Invoice command handlers
        $commandBus->register(CreateInvoiceCommand::class, CreateInvoiceCommandHandler::class);
        $commandBus->register(SendInvoiceCommand::class, SendInvoiceCommandHandler::class);
    }

    /**
     * Register query handlers with the query bus.
     */
    private function registerQueryHandlers(): void
    {
        $queryBus = $this->app->make(QueryBus::class);

        // Invoice query handlers
        $queryBus->register(GetInvoiceQuery::class, GetInvoiceQueryHandler::class);
        $queryBus->register(GetInvoicesQuery::class, GetInvoicesQueryHandler::class);
    }
}