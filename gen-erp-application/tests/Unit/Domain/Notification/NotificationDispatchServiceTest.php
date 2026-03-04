<?php

namespace Tests\Unit\Domain\Notification;

use App\Domain\Auth\Models\User;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Notification\Services\NotificationDispatchService;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationDispatchService $service;
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(NotificationDispatchService::class);
        
        // Create test company
        $this->company = Company::factory()->create();

        // Create test user
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'owner']);
    }

    /** @test */
    public function it_can_dispatch_notification_event()
    {
        Notification::fake();

        $event = new TestNotifiableEvent($this->user, $this->company->id);

        $this->service->dispatch($event);

        // Assert notification was stored in database
        $this->assertEquals(1, ErpNotification::count());
        
        $notification = ErpNotification::first();
        $this->assertEquals($this->company->id, $notification->tenant_id);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('test', $notification->domain);
        $this->assertEquals('test.event', $notification->type);

        // Assert broadcast notification was sent
        Notification::assertSentTo($this->user, \App\Domain\Notification\Notifications\ErpBroadcastNotification::class);
    }

    /** @test */
    public function it_creates_notifications_for_multiple_recipients()
    {
        Notification::fake();

        $user2 = User::factory()->create();
        $user2->companies()->attach($this->company->id, ['role' => 'user']);

        $event = new TestNotifiableEventMultipleUsers(
            collect([$this->user, $user2]), 
            $this->company->id
        );

        $this->service->dispatch($event);

        // Assert notifications were created for both users
        $this->assertEquals(2, ErpNotification::count());
        
        $userIds = ErpNotification::pluck('user_id')->sort()->values();
        $this->assertEquals([$this->user->id, $user2->id], $userIds->toArray());
    }

    /** @test */
    public function it_throws_exception_when_user_has_no_companies()
    {
        $userWithoutCompany = User::factory()->create();
        
        $event = new TestNotifiableEvent($userWithoutCompany, $this->company->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("User {$userWithoutCompany->id} has no associated companies");

        $this->service->dispatch($event);
    }

    /** @test */
    public function it_stores_notification_with_correct_data_structure()
    {
        Notification::fake();

        $event = new TestNotifiableEvent($this->user, $this->company->id);

        $this->service->dispatch($event);

        $notification = ErpNotification::first();
        
        $this->assertIsString($notification->id);
        $this->assertTrue(\Illuminate\Support\Str::isUuid($notification->id));
        $this->assertEquals($this->company->id, $notification->tenant_id);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('test', $notification->domain);
        $this->assertEquals('test.event', $notification->type);
        $this->assertEquals('test.title', $notification->title_key);
        $this->assertEquals('test.body', $notification->body_key);
        $this->assertEquals(['param' => 'value'], $notification->translation_params);
        $this->assertEquals('test-icon', $notification->icon);
        $this->assertEquals('info', $notification->color);
        $this->assertEquals('/test-url', $notification->action_url);
        $this->assertEquals('test.action', $notification->action_label_key);
        $this->assertEquals(['meta' => 'data'], $notification->meta);
        $this->assertNull($notification->read_at);
        $this->assertNotNull($notification->created_at);
        $this->assertNotNull($notification->updated_at);
    }
}

// Test helper classes
class TestNotifiableEvent implements NotifiableEvent
{
    public function __construct(
        private User $user,
        private int $tenantId
    ) {}

    public function getRecipients(): Collection
    {
        return collect([$this->user]);
    }

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'test',
            type: 'test.event',
            titleKey: 'test.title',
            bodyKey: 'test.body',
            translationParams: ['param' => 'value'],
            icon: 'test-icon',
            color: 'info',
            actionUrl: '/test-url',
            actionLabelKey: 'test.action',
            channel: 'user',
            roleTarget: null,
            meta: ['meta' => 'data']
        );
    }
}

class TestNotifiableEventMultipleUsers implements NotifiableEvent
{
    public function __construct(
        private Collection $users,
        private int $tenantId
    ) {}

    public function getRecipients(): Collection
    {
        return $this->users;
    }

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'test',
            type: 'test.multiple',
            titleKey: 'test.multiple.title',
            bodyKey: 'test.multiple.body',
            translationParams: [],
            icon: 'users',
            color: 'info',
            actionUrl: null,
            actionLabelKey: null,
            channel: 'tenant',
            roleTarget: null,
            meta: []
        );
    }
}