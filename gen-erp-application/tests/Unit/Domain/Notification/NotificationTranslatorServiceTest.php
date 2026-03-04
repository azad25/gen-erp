<?php

namespace Tests\Unit\Domain\Notification;

use App\Domain\Auth\Models\User;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Notification\Services\NotificationTranslatorService;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTranslatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationTranslatorService $service;
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(NotificationTranslatorService::class);
        
        // Create test company
        $this->company = Company::factory()->create();

        // Create test user with Bengali preference
        $this->user = User::factory()->create([
            'preferred_language' => 'bn',
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'owner']);
    }

    /** @test */
    public function it_translates_notification_to_user_preferred_language()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'action_label_key' => 'notifications.actions.view',
            'translation_params' => [
                'message' => 'Test system message',
            ],
        ]);

        $translated = $this->service->translateNotification($notification, $this->user);

        $this->assertArrayHasKey('translated_title', $translated);
        $this->assertArrayHasKey('translated_body', $translated);
        $this->assertArrayHasKey('translated_action_label', $translated);

        // Should use Bengali translations
        $this->assertEquals('সিস্টেম সতর্কতা', $translated['translated_title']);
        $this->assertStringContainsString('Test system message', $translated['translated_body']);
    }

    /** @test */
    public function it_falls_back_to_english_when_user_has_no_language_preference()
    {
        $userWithoutLanguage = User::factory()->create([
            'preferred_language' => null,
        ]);
        $userWithoutLanguage->companies()->attach($this->company->id, ['role' => 'user']);

        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $userWithoutLanguage->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'translation_params' => [
                'message' => 'Test system message',
            ],
        ]);

        $translated = $this->service->translateNotification($notification, $userWithoutLanguage);

        // Should use English translations
        $this->assertEquals('System Alert', $translated['translated_title']);
        $this->assertStringContainsString('Test system message', $translated['translated_body']);
    }

    /** @test */
    public function it_falls_back_to_english_for_unsupported_language()
    {
        $userWithUnsupportedLanguage = User::factory()->create([
            'preferred_language' => 'fr', // French not supported
        ]);
        $userWithUnsupportedLanguage->companies()->attach($this->company->id, ['role' => 'user']);

        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $userWithUnsupportedLanguage->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'translation_params' => [
                'message' => 'Test system message',
            ],
        ]);

        $translated = $this->service->translateNotification($notification, $userWithUnsupportedLanguage);

        // Should fall back to English
        $this->assertEquals('System Alert', $translated['translated_title']);
    }

    /** @test */
    public function it_handles_missing_translation_keys_gracefully()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title_key' => 'notifications.nonexistent.title',
            'body_key' => 'notifications.nonexistent.body',
            'action_label_key' => 'notifications.nonexistent.action',
            'translation_params' => [],
        ]);

        $translated = $this->service->translateNotification($notification, $this->user);

        // Should return the key itself when translation is missing
        $this->assertEquals('notifications.nonexistent.title', $translated['translated_title']);
        $this->assertEquals('notifications.nonexistent.body', $translated['translated_body']);
        $this->assertEquals('notifications.nonexistent.action', $translated['translated_action_label']);
    }

    /** @test */
    public function it_handles_null_action_label_key()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'action_label_key' => null,
            'translation_params' => [],
        ]);

        $translated = $this->service->translateNotification($notification, $this->user);

        $this->assertNull($translated['translated_action_label']);
    }

    /** @test */
    public function it_substitutes_translation_parameters_correctly()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title_key' => 'notifications.invoice.paid.title',
            'body_key' => 'notifications.invoice.paid.body',
            'translation_params' => [
                'number' => 'INV-1234',
                'amount' => '৳5,000',
            ],
        ]);

        $translated = $this->service->translateNotification($notification, $this->user);

        $this->assertEquals('চালান পরিশোধিত', $translated['translated_title']);
        $this->assertStringContainsString('INV-1234', $translated['translated_body']);
        $this->assertStringContainsString('৳5,000', $translated['translated_body']);
    }

    /** @test */
    public function it_handles_empty_translation_parameters()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'translation_params' => null,
        ]);

        $translated = $this->service->translateNotification($notification, $this->user);

        $this->assertArrayHasKey('translated_title', $translated);
        $this->assertArrayHasKey('translated_body', $translated);
        $this->assertEquals('সিস্টেম সতর্কতা', $translated['translated_title']);
    }

    /** @test */
    public function it_uses_english_for_user_with_english_preference()
    {
        $englishUser = User::factory()->create([
            'preferred_language' => 'en',
        ]);
        $englishUser->companies()->attach($this->company->id, ['role' => 'user']);

        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $englishUser->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'translation_params' => [
                'message' => 'Test message',
            ],
        ]);

        $translated = $this->service->translateNotification($notification, $englishUser);

        $this->assertEquals('System Alert', $translated['translated_title']);
        $this->assertStringContainsString('Test message', $translated['translated_body']);
    }
}