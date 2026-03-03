<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Invoice Domain Events
        \App\Domain\Invoice\Events\InvoiceSent::class => [
            \App\Domain\Invoice\Listeners\SendInvoiceNotification::class,
        ],
        
        \App\Domain\Invoice\Events\InvoiceCancelled::class => [
            // Add listeners for invoice cancellation
        ],
        
        \App\Domain\Invoice\Events\InvoiceCreated::class => [
            // Add listeners for invoice creation
        ],

        // SalesOrder Domain Events
        \App\Domain\SalesOrder\Events\SalesOrderConfirmed::class => [
            \App\Domain\SalesOrder\Listeners\NotifyOrderConfirmation::class,
        ],
        
        \App\Domain\SalesOrder\Events\SalesOrderCancelled::class => [
            // Add listeners for order cancellation
        ],

        // Customer Domain Events
        \App\Domain\Customer\Events\CustomerTransactionRecorded::class => [
            \App\Domain\Customer\Listeners\UpdateCustomerBalance::class,
        ],

        // Product Domain Events
        \App\Domain\Product\Events\ProductCreated::class => [
            // Add listeners for product creation
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}