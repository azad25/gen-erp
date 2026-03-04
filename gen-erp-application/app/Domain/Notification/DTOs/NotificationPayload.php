<?php

namespace App\Domain\Notification\DTOs;

class NotificationPayload
{
    public function __construct(
        // Domain identifier — 'invoice' | 'inventory' | 'hr' | 'crm'
        public readonly string  $domain,

        // Notification type — 'invoice.paid' | 'stock.low' | 'leave.approved'
        public readonly string  $type,

        // Translation key for title — 'notifications.invoice.paid.title'
        public readonly string  $titleKey,

        // Translation key for body — 'notifications.invoice.paid.body'
        public readonly string  $bodyKey,

        // Parameters injected into translation strings
        // e.g. ['number' => '1023', 'amount' => '৳5,000']
        public readonly array   $translationParams,

        // UI — icon name from your icon library
        public readonly string  $icon,

        // UI — 'success' | 'warning' | 'danger' | 'info'
        public readonly string  $color,

        // Where to navigate on click
        public readonly ?string $actionUrl,

        // Translation key for action button label
        public readonly ?string $actionLabelKey,

        // 'user' — only specific user
        // 'tenant' — all users in the tenant
        // 'role' — users with a specific role/permission
        public readonly string  $channel,

        // Required if channel = 'role'
        public readonly ?string $roleTarget,

        // Optional extra data (domain-specific IDs for deep linking)
        public readonly array   $meta = [],
    ) {}
}