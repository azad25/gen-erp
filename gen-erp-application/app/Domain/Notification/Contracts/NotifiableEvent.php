<?php

namespace App\Domain\Notification\Contracts;

use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Support\Collection;

/**
 * Any domain event that wants to trigger a notification
 * must implement this interface.
 *
 * The notification domain NEVER imports domain classes directly.
 * Domains reach INTO the notification domain via this contract only.
 */
interface NotifiableEvent
{
    /**
     * Who receives this notification.
     * Domain decides recipients based on its own business rules.
     */
    public function getRecipients(): Collection;

    /**
     * Standard notification payload.
     * Contains translation keys + params — never translated strings.
     */
    public function toNotificationPayload(): NotificationPayload;
}