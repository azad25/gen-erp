<?php

namespace App\Domain\Inbox\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Inbox\Models\Message;

class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        // User must be a participant of the conversation
        return $message->conversation->participants()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true; // All authenticated users can send messages
    }

    public function update(User $user, Message $message): bool
    {
        // Only the sender can edit their own messages
        return $message->sender_id === $user->id;
    }

    public function delete(User $user, Message $message): bool
    {
        // Only the sender can delete their own messages
        return $message->sender_id === $user->id;
    }
}
