<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app notification used by NotificationService.
 */
class GenericNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $eventKey,
        private readonly string $subject,
        private readonly string $body,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'subject' => $this->subject,
            'body' => $this->body,
        ];
    }
}
