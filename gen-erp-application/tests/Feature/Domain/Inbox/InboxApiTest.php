<?php

namespace Tests\Feature\Domain\Inbox;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Inbox\Models\Conversation;
use App\Domain\Inbox\Models\Message;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InboxApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user1;
    private User $user2;
    private User $user3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        
        $this->user1 = User::factory()->create();
        $this->user1->companies()->attach($this->company->id, ['role' => 'admin', 'is_active' => true]);
        
        $this->user2 = User::factory()->create();
        $this->user2->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);
        
        $this->user3 = User::factory()->create();
        $this->user3->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);
        
        CompanyContext::setActive($this->company);
        Storage::fake('private');
    }

    public function test_can_create_direct_conversation(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->postJson('/api/v1/inbox/conversations/direct', [
            'user_id' => $this->user2->id,
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'is_group',
                    'participants',
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            'company_id' => $this->company->id,
            'is_group' => false,
        ]);

        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $this->user1->id,
        ]);

        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $this->user2->id,
        ]);
    }

    public function test_cannot_create_duplicate_direct_conversation(): void
    {
        Sanctum::actingAs($this->user1);

        // Create first conversation
        $this->postJson('/api/v1/inbox/conversations/direct', [
            'user_id' => $this->user2->id,
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        // Try to create duplicate
        $response = $this->postJson('/api/v1/inbox/conversations/direct', [
            'user_id' => $this->user2->id,
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        // Should only have one conversation
        $this->assertEquals(1, Conversation::count());
    }

    public function test_can_create_group_conversation(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->postJson('/api/v1/inbox/conversations/group', [
            'title' => 'Project Team',
            'participant_ids' => [$this->user2->id, $this->user3->id],
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'is_group',
                    'participants',
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            'company_id' => $this->company->id,
            'title' => 'Project Team',
            'is_group' => true,
        ]);

        // Should have 3 participants (creator + 2 added)
        $conversation = Conversation::first();
        $this->assertEquals(3, $conversation->participants()->count());
    }

    public function test_can_send_message(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->attach([
            $this->user1->id => ['joined_at' => now()],
            $this->user2->id => ['joined_at' => now()],
        ]);

        $response = $this->postJson("/api/v1/inbox/conversations/{$conversation->id}/messages", [
            'content' => 'Hello, this is a test message!',
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'content',
                    'sender',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'content' => 'Hello, this is a test message!',
        ]);
    }

    public function test_can_send_message_with_attachments(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->attach([
            $this->user1->id => ['joined_at' => now()],
            $this->user2->id => ['joined_at' => now()],
        ]);

        $file = UploadedFile::fake()->image('document.jpg', 100, 100);

        $response = $this->postJson("/api/v1/inbox/conversations/{$conversation->id}/messages", [
            'content' => 'Check this out!',
            'attachments' => [$file],
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('message_attachments', [
            'file_name' => 'document.jpg',
        ]);

        Storage::disk('private')->assertExists(
            Message::first()->attachments()->first()->file_path
        );
    }

    public function test_can_list_conversations(): void
    {
        Sanctum::actingAs($this->user1);

        // Create multiple conversations
        $conv1 = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now()->subHours(2),
        ]);
        $conv1->participants()->attach([$this->user1->id, $this->user2->id]);

        $conv2 = Conversation::create([
            'company_id' => $this->company->id,
            'title' => 'Team Chat',
            'is_group' => true,
            'created_by' => $this->user1->id,
            'last_message_at' => now()->subHour(),
        ]);
        $conv2->participants()->attach([$this->user1->id, $this->user2->id, $this->user3->id]);

        $response = $this->getJson('/api/v1/inbox/conversations', [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'is_group',
                        'participants',
                        'unread_count',
                    ],
                ],
            ]);

        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_can_get_messages_for_conversation(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        // Create messages
        Message::create([
            'company_id' => $this->company->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'content' => 'First message',
        ]);

        Message::create([
            'company_id' => $this->company->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user2->id,
            'content' => 'Second message',
        ]);

        $response = $this->getJson("/api/v1/inbox/conversations/{$conversation->id}/messages", [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'sender',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_can_edit_own_message(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        $message = Message::create([
            'company_id' => $this->company->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'content' => 'Original message',
        ]);

        $response = $this->putJson("/api/v1/inbox/messages/{$message->id}", [
            'content' => 'Edited message',
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'content' => 'Edited message',
            'is_edited' => true,
        ]);
    }

    public function test_cannot_edit_others_message(): void
    {
        Sanctum::actingAs($this->user2);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        $message = Message::create([
            'company_id' => $this->company->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'content' => 'Original message',
        ]);

        $response = $this->putJson("/api/v1/inbox/messages/{$message->id}", [
            'content' => 'Trying to edit',
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertStatus(500);
    }

    public function test_can_delete_own_message(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        $message = Message::create([
            'company_id' => $this->company->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'content' => 'Message to delete',
        ]);

        $response = $this->deleteJson("/api/v1/inbox/messages/{$message->id}", [], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        $this->assertSoftDeleted('messages', [
            'id' => $message->id,
        ]);
    }

    public function test_can_star_conversation(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        $response = $this->postJson("/api/v1/inbox/conversations/{$conversation->id}/star", [], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['is_starred' => true],
            ]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->user1->id,
            'is_starred' => true,
        ]);
    }

    public function test_can_mark_conversation_as_read(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'is_group' => false,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        $response = $this->postJson("/api/v1/inbox/conversations/{$conversation->id}/read", [], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        $participant = $conversation->participants()->where('users.id', $this->user1->id)->first();
        $this->assertNotNull($participant->pivot->last_read_at);
    }

    public function test_multi_tenancy_isolation(): void
    {
        $company2 = Company::factory()->create();
        $user4 = User::factory()->create();
        $user4->companies()->attach($company2->id, ['role' => 'admin', 'is_active' => true]);

        CompanyContext::setActive($company2);

        $conversation = Conversation::create([
            'company_id' => $company2->id,
            'is_group' => false,
            'created_by' => $user4->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$user4->id]);

        // User from company1 should not see company2's conversations
        Sanctum::actingAs($this->user1);

        $response = $this->getJson('/api/v1/inbox/conversations', [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();
        $this->assertEquals(0, count($response->json('data')));
    }

    public function test_can_add_participants_to_group(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'title' => 'Team',
            'is_group' => true,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id]);

        $response = $this->postJson("/api/v1/inbox/conversations/{$conversation->id}/participants", [
            'user_ids' => [$this->user3->id],
        ], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        $this->assertEquals(3, $conversation->fresh()->participants()->count());
    }

    public function test_can_remove_participant_from_group(): void
    {
        Sanctum::actingAs($this->user1);

        $conversation = Conversation::create([
            'company_id' => $this->company->id,
            'title' => 'Team',
            'is_group' => true,
            'created_by' => $this->user1->id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$this->user1->id, $this->user2->id, $this->user3->id]);

        $response = $this->deleteJson("/api/v1/inbox/conversations/{$conversation->id}/participants/{$this->user3->id}", [], [
            'X-Company-ID' => $this->company->id,
        ]);

        $response->assertOk();

        $this->assertEquals(2, $conversation->fresh()->participants()->count());
    }
}
