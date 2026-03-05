<?php

namespace App\Domain\Inbox\Services;

use App\Domain\Inbox\Models\Conversation;
use App\Domain\Inbox\Models\Message;
use App\Domain\Inbox\Models\MessageAttachment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InboxService
{
    public function getConversationsForUser(int $userId, int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = Conversation::forCompany($companyId)
            ->forUser($userId)
            ->with(['participants', 'messages' => function ($q) {
                $q->latest()->limit(1)->with('sender');
            }])
            ->orderBy('last_message_at', 'desc');

        // Filter starred conversations
        if (isset($filters['starred']) && $filters['starred']) {
            $query->starredByUser($userId);
        }

        // Search by title or participant name
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search, $userId) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('participants', function ($q) use ($search, $userId) {
                      $q->where('users.id', '!=', $userId)
                        ->where('users.name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function createDirectConversation(int $userId, int $otherUserId, int $companyId): Conversation
    {
        return DB::transaction(function () use ($userId, $otherUserId, $companyId) {
            // Check if conversation already exists
            $existing = Conversation::forCompany($companyId)
                ->where('is_group', false)
                ->whereHas('participants', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                })
                ->whereHas('participants', function ($q) use ($otherUserId) {
                    $q->where('users.id', $otherUserId);
                })
                ->first();

            if ($existing) {
                return $existing;
            }

            // Create new conversation
            $conversation = Conversation::create([
                'company_id' => $companyId,
                'is_group' => false,
                'created_by' => $userId,
                'last_message_at' => now(),
            ]);

            // Add participants
            $conversation->participants()->attach([
                $userId => ['joined_at' => now()],
                $otherUserId => ['joined_at' => now()],
            ]);

            return $conversation->fresh(['participants']);
        });
    }

    public function createGroupConversation(int $userId, array $participantIds, string $title, int $companyId): Conversation
    {
        return DB::transaction(function () use ($userId, $participantIds, $title, $companyId) {
            $conversation = Conversation::create([
                'company_id' => $companyId,
                'title' => $title,
                'is_group' => true,
                'created_by' => $userId,
                'last_message_at' => now(),
            ]);

            // Add creator and participants
            $allParticipants = array_unique(array_merge([$userId], $participantIds));
            
            $pivotData = [];
            foreach ($allParticipants as $participantId) {
                $pivotData[$participantId] = ['joined_at' => now()];
            }

            $conversation->participants()->attach($pivotData);

            return $conversation->fresh(['participants']);
        });
    }

    public function sendMessage(int $conversationId, int $senderId, string $content, array $attachments = []): Message
    {
        return DB::transaction(function () use ($conversationId, $senderId, $content, $attachments) {
            $conversation = Conversation::findOrFail($conversationId);

            // Verify sender is a participant
            if (!$conversation->participants()->where('users.id', $senderId)->exists()) {
                throw new \Exception('User is not a participant of this conversation');
            }

            $message = Message::create([
                'company_id' => $conversation->company_id,
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'content' => $content,
            ]);

            // Handle attachments
            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    $this->attachFileToMessage($message, $file);
                }
            }

            return $message->fresh(['attachments', 'sender']);
        });
    }

    public function getMessages(int $conversationId, int $userId, int $companyId, int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        $conversation = Conversation::forCompany($companyId)->findOrFail($conversationId);

        // Verify user is a participant
        if (!$conversation->participants()->where('users.id', $userId)->exists()) {
            throw new \Exception('User is not a participant of this conversation');
        }

        return Message::where('conversation_id', $conversationId)
            ->with(['sender', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function editMessage(int $messageId, int $userId, string $newContent): Message
    {
        $message = Message::findOrFail($messageId);

        if (!$message->canBeEditedBy($userId)) {
            throw new \Exception('You cannot edit this message');
        }

        $message->edit($newContent);

        return $message->fresh();
    }

    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = Message::findOrFail($messageId);

        if (!$message->canBeDeletedBy($userId)) {
            throw new \Exception('You cannot delete this message');
        }

        return $message->delete();
    }

    public function deleteConversation(int $conversationId, int $userId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Remove user from participants
        $conversation->participants()->detach($userId);

        // If no participants left, delete the conversation
        if ($conversation->participants()->count() === 0) {
            return $conversation->delete();
        }

        return true;
    }

    public function addParticipants(int $conversationId, array $userIds, int $addedBy): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->is_group) {
            throw new \Exception('Cannot add participants to a direct conversation');
        }

        // Verify the user adding participants is a participant
        if (!$conversation->participants()->where('users.id', $addedBy)->exists()) {
            throw new \Exception('You are not a participant of this conversation');
        }

        $pivotData = [];
        foreach ($userIds as $userId) {
            if (!$conversation->participants()->where('users.id', $userId)->exists()) {
                $pivotData[$userId] = ['joined_at' => now()];
            }
        }

        $conversation->participants()->attach($pivotData);
    }

    public function removeParticipant(int $conversationId, int $userIdToRemove, int $removedBy): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->is_group) {
            throw new \Exception('Cannot remove participants from a direct conversation');
        }

        // Only creator or the user themselves can remove
        if ($conversation->created_by !== $removedBy && $userIdToRemove !== $removedBy) {
            throw new \Exception('You do not have permission to remove this participant');
        }

        $conversation->participants()->detach($userIdToRemove);
    }

    public function getCompanyUsers(int $companyId, int $currentUserId): Collection
    {
        return \App\Domain\Auth\Models\User::whereHas('companies', function ($q) use ($companyId) {
            $q->where('companies.id', $companyId)
              ->where('company_user.is_active', true);
        })
        ->where('id', '!=', $currentUserId)
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'avatar_url']);
    }

    private function attachFileToMessage(Message $message, UploadedFile $file): MessageAttachment
    {
        $path = $file->store("inbox/{$message->company_id}/{$message->conversation_id}", 'private');

        return MessageAttachment::create([
            'company_id' => $message->company_id,
            'message_id' => $message->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
