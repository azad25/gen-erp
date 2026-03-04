<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Notification\Notifications\ErpBroadcastNotification;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Str;

class NotificationDispatchService
{
    /**
     * Central dispatch method.
     * Called by the universal listener for every notifiable event.
     * Never knows about Invoice, HR, CRM, or any domain.
     */
    public function dispatch(NotifiableEvent $event): void
    {
        $payload    = $event->toNotificationPayload();
        $recipients = $event->getRecipients();

        foreach ($recipients as $user) {
            $notification = $this->store($payload, $user);
            $this->broadcast($notification, $user);
        }
    }

    /**
     * Store notification in database using keys — not translated text.
     */
    private function store(NotificationPayload $payload, User $user): ErpNotification
    {
        // Get tenant_id from user's companies relationship
        $tenantId = $user->companies()->first()?->id;
        
        if (!$tenantId) {
            throw new \Exception("User {$user->id} has no associated companies");
        }

        return ErpNotification::create([
            'id'                 => (string) Str::uuid(),
            'tenant_id'          => $tenantId,
            'user_id'            => $user->id,
            'domain'             => $payload->domain,
            'type'               => $payload->type,
            'title_key'          => $payload->titleKey,
            'body_key'           => $payload->bodyKey,
            'translation_params' => $payload->translationParams,
            'icon'               => $payload->icon,
            'color'              => $payload->color,
            'action_url'         => $payload->actionUrl,
            'action_label_key'   => $payload->actionLabelKey,
            'meta'               => $payload->meta,
        ]);
    }

    /**
     * Broadcast over WebSocket.
     * Sends raw keys + params — frontend translates using user's locale.
     */
    private function broadcast(ErpNotification $notification, User $user): void
    {
        $user->notify(new ErpBroadcastNotification($notification));
    }
}