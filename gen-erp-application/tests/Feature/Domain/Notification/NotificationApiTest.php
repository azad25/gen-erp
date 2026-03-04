<?php

namespace Tests\Feature\Domain\Notification;

use App\Domain\Auth\Models\User;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@user.com',
            'preferred_language' => 'bn',
        ]);

        // Attach user to company
        $this->user->companies()->attach($this->company->id, ['role' => 'owner']);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_user_notifications()
    {
        // Create test notifications
        ErpNotification::factory()->count(3)->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        // Create notification for another user (should not appear)
        $anotherUser = User::factory()->create();
        $anotherUser->companies()->attach($this->company->id, ['role' => 'user']);
        ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->getJson('/api/v1/erp-notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        
        // Assert all notifications belong to the authenticated user
        $notifications = $response->json('data');
        foreach ($notifications as $notification) {
            $this->assertEquals($this->user->id, $notification['user_id']);
        }
    }

    /** @test */
    public function it_can_get_unread_count()
    {
        // Create 2 unread and 1 read notification
        ErpNotification::factory()->count(2)->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/erp-notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['count' => 2]);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->postJson("/api/v1/erp-notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function it_cannot_mark_another_users_notification_as_read()
    {
        $anotherUser = User::factory()->create();
        $anotherUser->companies()->attach($this->company->id, ['role' => 'user']);
        
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $anotherUser->id,
            'read_at' => null,
        ]);

        $response = $this->postJson("/api/v1/erp-notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        // Create multiple unread notifications
        ErpNotification::factory()->count(3)->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->postJson('/api/v1/erp-notifications/read-all');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert all notifications are now read
        $unreadCount = ErpNotification::where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->count();
        
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_can_delete_notification()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/erp-notifications/{$notification->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('erp_notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function it_cannot_delete_another_users_notification()
    {
        $anotherUser = User::factory()->create();
        $anotherUser->companies()->attach($this->company->id, ['role' => 'user']);
        
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->deleteJson("/api/v1/erp-notifications/{$notification->id}");

        $response->assertStatus(404);
        
        $this->assertDatabaseHas('erp_notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        // Remove authentication by not calling Sanctum::actingAs()
        $this->app['auth']->forgetGuards();

        $endpoints = [
            ['GET', '/api/v1/erp-notifications'],
            ['GET', '/api/v1/erp-notifications/unread-count'],
            ['POST', '/api/v1/erp-notifications/fake-id/read'],
            ['POST', '/api/v1/erp-notifications/read-all'],
            ['DELETE', '/api/v1/erp-notifications/fake-id'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_paginates_notifications_correctly()
    {
        // Create 25 notifications
        ErpNotification::factory()->count(25)->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/erp-notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data'); // Default pagination is 20
        $response->assertJsonStructure([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        $this->assertEquals(25, $response->json('total'));
        $this->assertEquals(2, $response->json('last_page'));
    }

    /** @test */
    public function it_returns_notifications_in_latest_first_order()
    {
        // Create notifications with different timestamps
        $old = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(2),
        ]);

        $recent = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subMinutes(30),
        ]);

        $newest = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/erp-notifications');

        $response->assertStatus(200);
        $notifications = $response->json('data');

        // Assert order is newest first
        $this->assertEquals($newest->id, $notifications[0]['id']);
        $this->assertEquals($recent->id, $notifications[1]['id']);
        $this->assertEquals($old->id, $notifications[2]['id']);
    }

    /** @test */
    public function it_includes_translated_content_in_response()
    {
        $notification = ErpNotification::factory()->create([
            'tenant_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'translation_params' => ['message' => 'Test message'],
        ]);

        $response = $this->getJson('/api/v1/erp-notifications');

        $response->assertStatus(200);
        $notificationData = $response->json('data.0');

        // Assert translated content is included
        $this->assertArrayHasKey('translated_title', $notificationData);
        $this->assertArrayHasKey('translated_body', $notificationData);
        $this->assertArrayHasKey('translated_action_label', $notificationData);
    }
}