<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Auth\Models\User;

class NotificationTranslatorService
{
    // Supported locales — add new languages here ONLY
    private const SUPPORTED_LOCALES = ['bn', 'en'];
    private const DEFAULT_LOCALE    = 'bn';
    private const FALLBACK_LOCALE   = 'en';

    /**
     * Translate a notification for a specific user.
     * Reads user's preferred language from their profile.
     */
    public function translateForUser(
        string $titleKey,
        string $bodyKey,
        array  $params,
        User   $user,
        ?string $actionLabelKey = null,
    ): array {
        $locale = $this->resolveLocale($user->preferred_language ?? null);

        return [
            'title'        => $this->translate($titleKey, $params, $locale),
            'body'         => $this->translate($bodyKey,  $params, $locale),
            'action_label' => $actionLabelKey
                ? $this->translate($actionLabelKey, [], $locale)
                : null,
            'locale'       => $locale,
        ];
    }

    /**
     * Translate for API response — uses requesting user's language.
     */
    public function translateNotification(
        ErpNotification $notification,
        User            $user,
    ): array {
        $translated = $this->translateForUser(
            titleKey:       $notification->title_key,
            bodyKey:        $notification->body_key,
            params:         $notification->translation_params ?? [],
            user:           $user,
            actionLabelKey: $notification->action_label_key,
        );

        return [
            'translated_title' => $translated['title'],
            'translated_body' => $translated['body'],
            'translated_action_label' => $translated['action_label'],
            'locale' => $translated['locale'],
        ];
    }

    private function translate(string $key, array $params, string $locale): string
    {
        $translated = __($key, $params, $locale);

        // Fallback to English if Bengali key missing
        if ($translated === $key && $locale !== self::FALLBACK_LOCALE) {
            $translated = __($key, $params, self::FALLBACK_LOCALE);
        }

        return $translated;
    }

    private function resolveLocale(?string $userLocale): string
    {
        if ($userLocale && in_array($userLocale, self::SUPPORTED_LOCALES)) {
            return $userLocale;
        }
        return self::FALLBACK_LOCALE; // Changed from DEFAULT_LOCALE to FALLBACK_LOCALE for proper fallback
    }

    /**
     * When adding a new language:
     * 1. Add locale code to SUPPORTED_LOCALES
     * 2. Create lang/{locale}/notifications.php
     * 3. Done — no other code changes needed
     */
}