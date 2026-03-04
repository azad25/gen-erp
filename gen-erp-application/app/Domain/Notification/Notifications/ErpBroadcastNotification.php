<?php

namespace App\Domain\Notification\Notifications;

use App\Domain\Notification\Models\ErpNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ErpBroadcastNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(
        private readonly ErpNotification $notification,
    ) {}

    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    /**
     * Broadcast raw keys + params.
     * Frontend receives this and translates using user's stored locale.
     * This means the same WebSocket payload works for ALL languages.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id'                 => $this->notification->id,
            'domain'             => $this->notification->domain,
            'type'               => $this->notification->type,

            // Keys — NOT translated strings
            'title_key'          => $this->notification->title_key,
            'body_key'           => $this->notification->body_key,
            'translation_params' => $this->notification->translation_params,
            'action_label_key'   => $this->notification->action_label_key,

            // UI data — language agnostic
            'icon'               => $this->notification->icon,
            'color'              => $this->notification->color,
            'action_url'         => $this->notification->action_url,
            'meta'               => $this->notification->meta,
            'read'               => false,
            'created_at'         => $this->notification->created_at->toISOString(),
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->notification->user_id}"),
        ];
    }
}