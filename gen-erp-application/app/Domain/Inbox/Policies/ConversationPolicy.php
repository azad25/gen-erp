<?php

namespace App\Domain\Inbox\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Inbox\Models\Conversation;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view conversations
    }

    public function view(User $user, Conversation $conversation): bool
    {
        // User must be a participant of the conversation
        return $conversation->participants()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true; // All authenticated users can create conversations
    }

    public function update(User $user, Conversation $conversation): bool
    {
        // Only group creator can update group details
        return $conversation->is_group && $conversation->created_by === $user->id;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        // Users can leave conversations (delete for themselves)
        return $conversation->participants()->where('users.id', $user->id)->exists();
    }

    public function addParticipants(User $user, Conversation $conversation): bool
    {
        // Only group conversations can have participants added
        // User must be a participant
        return $conversation->is_group 
            && $conversation->participants()->where('users.id', $user->id)->exists();
    }

    public function removeParticipants(User $user, Conversation $conversation): bool
    {
        // Creator can remove anyone, users can remove themselves
        return $conversation->is_group 
            && ($conversation->created_by === $user->id 
                || $conversation->participants()->where('users.id', $user->id)->exists());
    }
}
