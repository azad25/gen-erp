<?php

namespace App\Services;

use App\Enums\NotificationEvent;
use App\Models\Company;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Multi-channel notification dispatcher with template rendering.
 */
class NotificationService
{
    /**
     * Send notification to specific users.
     *
     * @param  array<string, string>  $variables
     * @param  array<int, int>  $targetUserIds
     */
    public function send(NotificationEvent $event, Company $company, array $variables, array $targetUserIds): void
    {
        $channels = ['in_app', 'email'];

        foreach ($channels as $channel) {
            $template = $this->resolveTemplate($company, $event, $channel);
            if (! $template || ! $template->is_active) {
                continue;
            }

            $renderedBody = $this->render($template->body, $variables);
            $renderedSubject = $template->subject ? $this->render($template->subject, $variables) : $event->label();

            foreach ($targetUserIds as $userId) {
                $user = User::find($userId);
                if (! $user || ! $this->userWantsNotification($user, $event, $channel, $company->id)) {
                    continue;
                }

                if ($channel === 'in_app') {
                    // Use Laravel's database notification channel
                    $user->notify(new \App\Notifications\GenericNotification(
                        $event->value,
                        $renderedSubject,
                        $renderedBody,
                    ));
                } elseif ($channel === 'email') {
                    // TODO: Phase 7 — dispatch queued email via SendNotificationJob
                    Log::info('Email notification queued', [
                        'event' => $event->value,
                        'user' => $userId,
                        'subject' => $renderedSubject,
                    ]);
                }
            }
        }
    }

    /**
     * Send to all users with a given role in the company.
     *
     * @param  array<string, string>  $variables
     */
    public function sendToRole(NotificationEvent $event, Company $company, array $variables, string $role): void
    {
        $userIds = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('role', $role)
            ->pluck('user_id')
            ->toArray();

        $this->send($event, $company, $variables, $userIds);
    }

    /**
     * Replace {placeholder} variables in template body.
     */
    public function render(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace($key, $value ?? '', $body);
        }

        // Remove any unreplaced placeholders
        return preg_replace('/\{[a-z_]+\}/', '', $body) ?? $body;
    }

    private function resolveTemplate(Company $company, NotificationEvent $event, string $channel): ?NotificationTemplate
    {
        // Company-specific template first, then system default
        return NotificationTemplate::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('event_key', $event->value)
            ->where('channel', $channel)
            ->first()
            ?? NotificationTemplate::withoutGlobalScopes()
                ->where('is_system', true)
                ->where('event_key', $event->value)
                ->where('channel', $channel)
                ->first();
    }

    private function userWantsNotification(User $user, NotificationEvent $event, string $channel, int $companyId): bool
    {
        $pref = DB::table('notification_preferences')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('event_key', $event->value)
            ->first();

        if (! $pref) {
            return true; // Default: send all
        }

        return (bool) $pref->{$channel};
    }
}
