<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use App\Domain\Auth\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

class SystemAlertFired implements ShouldBroadcast, NotifiableEvent
{
    use InteractsWithSockets, Dispatchable;

    public function __construct(
        public readonly string $message,
        public readonly string $level = 'info', // info, warning, danger, success
        public readonly int $tenantId,
        public readonly ?int $userId = null, // null = all users in tenant
    ) {}

    public function getRecipients(): Collection
    {
        if ($this->userId) {
            // Send to specific user
            return collect([User::find($this->userId)])->filter();
        }

        // Send to all users in tenant (company)
        // Users have many-to-many relationship with companies
        return User::whereHas('companies', function($query) {
            $query->where('companies.id', $this->tenantId);
        })->get();
    }

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain:            'system',
            type:              'system.alert',
            titleKey:          'notifications.system.alert.title',
            bodyKey:           'notifications.system.alert.body',
            translationParams: [
                'message' => $this->message,
            ],
            icon:              $this->getIcon(),
            color:             $this->level,
            actionUrl:         null,
            actionLabelKey:    null,
            channel:           $this->userId ? 'user' : 'tenant',
            roleTarget:        null,
            meta:              [
                'level' => $this->level,
            ],
        );
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}")];
    }

    private function getIcon(): string
    {
        return match($this->level) {
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'danger' => 'x-circle',
            default => 'info-circle',
        };
    }
}