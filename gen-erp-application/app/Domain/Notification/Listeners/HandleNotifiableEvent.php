<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\Services\NotificationDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ONE listener handles ALL domain events.
 * This file NEVER changes when new domains are added.
 * Zero imports of domain-specific classes.
 */
class HandleNotifiableEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public function __construct(
        private readonly NotificationDispatchService $dispatcher,
    ) {}

    /**
     * Handle any event that implements NotifiableEvent.
     * Laravel's event system routes it here automatically.
     */
    public function handle(NotifiableEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    public function failed(NotifiableEvent $event, Throwable $e): void
    {
        Log::error('Notification dispatch failed', [
            'event' => get_class($event),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}