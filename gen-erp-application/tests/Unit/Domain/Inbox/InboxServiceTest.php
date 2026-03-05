<?php

namespace Tests\Unit\Domain\Inbox;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Inbox\Models\Conversation;
use App\Domain\Inbox\Models\Message;
use App\Domain\Inbox\Services\InboxService;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxServiceTest extends TestCase
{
    use RefreshDatabase;

    private InboxService $service;
    private Company $company;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new InboxService();
        $this->company = Company::factory()->create();
        
        $this->user1 = User::factory()->create();
        $this->user1->companies()->attach($this->company->id, ['role' => 'admin', 'is_active' => true]);
        
        $this->user2 = User::factory()->create();
        $this->user2->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);
        
        CompanyContext::setActive($this->company);
    }

    public function test_creates_direct_conversation(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $this->assertFalse($conversation->is_group);
        $this->assertEquals(2, $conversation->participants()->count());
        $this->assertTrue($conversation->participants()->where('users.id', $this->user1->id)->exists());
        $this->assertTrue($conversation->participants()->where('users.id', $this->user2->id)->exists());
    }

    public function test_does_not_create_duplicate_direct_conversation(): void
    {
        $conv1 = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $conv2 = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $this->assertEquals($conv1->id, $conv2->id);
        $this->assertEquals(1, Conversation::count());
    }

    public function test_creates_group_conversation(): void
    {
        $user3 = User::factory()->create();
        $user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);

        $conversation = $this->service->createGroupConversation(
            $this->user1->id,
            [$this->user2->id, $user3->id],
            'Project Team',
            $this->company->id
        );

        $this->assertTrue($conversation->is_group);
        $this->assertEquals('Project Team', $conversation->title);
        $this->assertEquals(3, $conversation->participants()->count());
    }

    public function test_sends_message(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $message = $this->service->sendMessage(
            $conversation->id,
            $this->user1->id,
            'Hello, this is a test message!'
        );

        $this->assertEquals('Hello, this is a test message!', $message->content);
        $this->assertEquals($this->user1->id, $message->sender_id);
        $this->assertEquals($conversation->id, $message->conversation_id);
    }

    public function test_cannot_send_message_if_not_participant(): void
    {
        $user3 = User::factory()->create();
        $user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);

        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is not a participant of this conversation');

        $this->service->sendMessage(
            $conversation->id,
            $user3->id,
            'Trying to send message'
        );
    }

    public function test_edits_message(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $message = $this->service->sendMessage(
            $conversation->id,
            $this->user1->id,
            'Original message'
        );

        $editedMessage = $this->service->editMessage(
            $message->id,
            $this->user1->id,
            'Edited message'
        );

        $this->assertEquals('Edited message', $editedMessage->content);
        $this->assertTrue($editedMessage->is_edited);
        $this->assertNotNull($editedMessage->edited_at);
    }

    public function test_cannot_edit_others_message(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $message = $this->service->sendMessage(
            $conversation->id,
            $this->user1->id,
            'Original message'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot edit this message');

        $this->service->editMessage(
            $message->id,
            $this->user2->id,
            'Trying to edit'
        );
    }

    public function test_deletes_message(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $message = $this->service->sendMessage(
            $conversation->id,
            $this->user1->id,
            'Message to delete'
        );

        $result = $this->service->deleteMessage($message->id, $this->user1->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_adds_participants_to_group(): void
    {
        $user3 = User::factory()->create();
        $user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);

        $conversation = $this->service->createGroupConversation(
            $this->user1->id,
            [$this->user2->id],
            'Team',
            $this->company->id
        );

        $this->assertEquals(2, $conversation->participants()->count());

        $this->service->addParticipants($conversation->id, [$user3->id], $this->user1->id);

        $this->assertEquals(3, $conversation->fresh()->participants()->count());
    }

    public function test_cannot_add_participants_to_direct_conversation(): void
    {
        $user3 = User::factory()->create();
        $user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);

        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot add participants to a direct conversation');

        $this->service->addParticipants($conversation->id, [$user3->id], $this->user1->id);
    }

    public function test_removes_participant_from_group(): void
    {
        $user3 = User::factory()->create();
        $user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);

        $conversation = $this->service->createGroupConversation(
            $this->user1->id,
            [$this->user2->id, $user3->id],
            'Team',
            $this->company->id
        );

        $this->assertEquals(3, $conversation->participants()->count());

        $this->service->removeParticipant($conversation->id, $user3->id, $this->user1->id);

        $this->assertEquals(2, $conversation->fresh()->participants()->count());
    }

    public function test_gets_company_users(): void
    {
        $user3 = User::factory()->create();
        $user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);

        $users = $this->service->getCompanyUsers($this->company->id, $this->user1->id);

        // Should return user2 and user3, but not user1 (current user)
        $this->assertEquals(2, $users->count());
        $this->assertFalse($users->contains('id', $this->user1->id));
        $this->assertTrue($users->contains('id', $this->user2->id));
        $this->assertTrue($users->contains('id', $user3->id));
    }

    public function test_conversation_unread_count(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        // User1 sends 3 messages
        $this->service->sendMessage($conversation->id, $this->user1->id, 'Message 1');
        $this->service->sendMessage($conversation->id, $this->user1->id, 'Message 2');
        $this->service->sendMessage($conversation->id, $this->user1->id, 'Message 3');

        // User2 should have 3 unread messages
        $unreadCount = $conversation->fresh()->getUnreadCount($this->user2->id);
        $this->assertEquals(3, $unreadCount);

        // User1 should have 0 unread messages (they sent them)
        $unreadCount = $conversation->fresh()->getUnreadCount($this->user1->id);
        $this->assertEquals(0, $unreadCount);
    }

    public function test_marks_conversation_as_read(): void
    {
        $conversation = $this->service->createDirectConversation(
            $this->user1->id,
            $this->user2->id,
            $this->company->id
        );

        $this->service->sendMessage($conversation->id, $this->user1->id, 'Message 1');
        $this->service->sendMessage($conversation->id, $this->user1->id, 'Message 2');

        $this->assertEquals(2, $conversation->fresh()->getUnreadCount($this->user2->id));

        $conversation->markAsRead($this->user2->id);

        $this->assertEquals(0, $conversation->fresh()->getUnreadCount($this->user2->id));
    }
}
