<?php

namespace Tests\Feature\Domain\Notification;

use App\Domain\Auth\Models\User;
use App\Domain\Notification\Events\SystemAlertFired;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
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
    }

    /** @test */
    public function it_can_dispatch_system_alert_notification()
    {
        // Check initial count
        $this->assertEquals(0, ErpNotification::count());
        
        // Fire the event
        $event = new SystemAlertFired(
            message: 'Test system alert',
            level: 'info',
            tenantId: $this->company->id,
            userId: $this->user->id
        );

        event($event);

        // Process any queued jobs
        $this->artisan('queue:work', ['--once' => true]);

        // Assert the notification was created
        $this->assertGreaterThanOrEqual(1, ErpNotification::count());
        $notification = ErpNotification::where('user_id', $this->user->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals($this->user->id, $notification->user_id);
    }

    /** @test */
    public function it_stores_notification_in_database_when_event_is_processed()
    {
        // Process queue synchronously for testing
        Queue::fake();
        
        $this->assertEquals(0, ErpNotification::count());

        // Fire the event
        $event = new SystemAlertFired(
            message: 'Test system alert',
            level: 'warning',
            tenantId: $this->company->id,
            userId: $this->user->id
        );

        // Process the event directly (simulate queue processing)
        $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
            app(\App\Domain\Notification\Services\NotificationDispatchService::class)
        );
        $listener->handle($event);

        // Assert notification was stored
        $this->assertEquals(1, ErpNotification::count());

        $notification = ErpNotification::first();
        $this->assertEquals($this->company->id, $notification->tenant_id);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('system', $notification->domain);
        $this->assertEquals('system.alert', $notification->type);
        $this->assertEquals('notifications.system.alert.title', $notification->title_key);
        $this->assertEquals('notifications.system.alert.body', $notification->body_key);
        $this->assertEquals(['message' => 'Test system alert'], $notification->translation_params);
        $this->assertEquals('exclamation-triangle', $notification->icon);
        $this->assertEquals('warning', $notification->color);
    }

    /** @test */
    public function it_sends_notification_to_specific_user()
    {
        // Create another user in the same company
        $anotherUser = User::factory()->create(['name' => 'Another User']);
        $anotherUser->companies()->attach($this->company->id, ['role' => 'user']);

        // Fire event for specific user
        $event = new SystemAlertFired(
            message: 'User-specific alert',
            level: 'success',
            tenantId: $this->company->id,
            userId: $this->user->id
        );

        // Process the event
        $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
            app(\App\Domain\Notification\Services\NotificationDispatchService::class)
        );
        $listener->handle($event);

        // Assert only one notification was created for the specific user
        $this->assertEquals(1, ErpNotification::count());
        $notification = ErpNotification::first();
        $this->assertEquals($this->user->id, $notification->user_id);
    }

    /** @test */
    public function it_sends_notification_to_all_users_in_tenant_when_user_id_is_null()
    {
        // Create multiple users in the same company
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user3 = User::factory()->create(['name' => 'User 3']);
        
        $user2->companies()->attach($this->company->id, ['role' => 'user']);
        $user3->companies()->attach($this->company->id, ['role' => 'user']);

        // Fire event for all users in tenant (userId = null)
        $event = new SystemAlertFired(
            message: 'Tenant-wide alert',
            level: 'danger',
            tenantId: $this->company->id,
            userId: null
        );

        // Process the event
        $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
            app(\App\Domain\Notification\Services\NotificationDispatchService::class)
        );
        $listener->handle($event);

        // Assert notifications were created for all users in the tenant
        $this->assertEquals(3, ErpNotification::count());
        
        $userIds = ErpNotification::pluck('user_id')->sort()->values();
        $expectedUserIds = [$this->user->id, $user2->id, $user3->id];
        sort($expectedUserIds);
        
        $this->assertEquals($expectedUserIds, $userIds->toArray());
    }

    /** @test */
    public function it_does_not_send_notification_to_users_in_different_tenants()
    {
        // Create another company and user
        $anotherCompany = Company::factory()->create(['name' => 'Another Company']);
        $userInAnotherCompany = User::factory()->create(['name' => 'User in Another Company']);
        $userInAnotherCompany->companies()->attach($anotherCompany->id, ['role' => 'owner']);

        // Fire event for our company
        $event = new SystemAlertFired(
            message: 'Company-specific alert',
            level: 'info',
            tenantId: $this->company->id,
            userId: null
        );

        // Process the event
        $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
            app(\App\Domain\Notification\Services\NotificationDispatchService::class)
        );
        $listener->handle($event);

        // Assert only users from our company received notifications
        $this->assertEquals(1, ErpNotification::count());
        $notification = ErpNotification::first();
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals($this->company->id, $notification->tenant_id);
    }

    /** @test */
    public function it_handles_different_alert_levels_correctly()
    {
        $levels = [
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'danger' => 'x-circle',
            'info' => 'info-circle',
        ];

        foreach ($levels as $level => $expectedIcon) {
            $event = new SystemAlertFired(
                message: "Test {$level} alert",
                level: $level,
                tenantId: $this->company->id,
                userId: $this->user->id
            );

            // Process the event
            $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
                app(\App\Domain\Notification\Services\NotificationDispatchService::class)
            );
            $listener->handle($event);
        }

        $this->assertEquals(4, ErpNotification::count());

        foreach ($levels as $level => $expectedIcon) {
            $notification = ErpNotification::where('color', $level)->first();
            $this->assertNotNull($notification);
            $this->assertEquals($expectedIcon, $notification->icon);
            $this->assertEquals($level, $notification->color);
        }
    }

    /** @test */
    public function it_throws_exception_when_user_has_no_companies()
    {
        // Create user without company
        $userWithoutCompany = User::factory()->create(['name' => 'User Without Company']);

        $event = new SystemAlertFired(
            message: 'Test alert',
            level: 'info',
            tenantId: $this->company->id,
            userId: $userWithoutCompany->id
        );

        // Process the event and expect exception
        $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
            app(\App\Domain\Notification\Services\NotificationDispatchService::class)
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("User {$userWithoutCompany->id} has no associated companies");

        $listener->handle($event);
    }

    /** @test */
    public function it_creates_unique_notification_ids()
    {
        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $event = new SystemAlertFired(
                message: "Test alert {$i}",
                level: 'info',
                tenantId: $this->company->id,
                userId: $this->user->id
            );

            $listener = new \App\Domain\Notification\Listeners\HandleNotifiableEvent(
                app(\App\Domain\Notification\Services\NotificationDispatchService::class)
            );
            $listener->handle($event);
        }

        $this->assertEquals(3, ErpNotification::count());

        // Assert all IDs are unique UUIDs
        $ids = ErpNotification::pluck('id');
        $this->assertEquals(3, $ids->unique()->count());
        
        foreach ($ids as $id) {
            $this->assertTrue(\Illuminate\Support\Str::isUuid($id));
        }
    }
}