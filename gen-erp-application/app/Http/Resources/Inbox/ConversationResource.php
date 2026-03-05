<?php

namespace App\Http\Resources\Inbox;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()->id;
        $participant = $this->participants->firstWhere('id', $userId);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->getDisplayTitle($userId),
            'is_group' => $this->is_group,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'is_starred' => $participant?->pivot->is_starred ?? false,
            'is_muted' => $participant?->pivot->is_muted ?? false,
            'unread_count' => $this->getUnreadCount($userId),
            'participants' => $this->participants->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ]),
            'last_message' => $this->when(
                $this->relationLoaded('messages') && $this->messages->isNotEmpty(),
                function () {
                    $lastMessage = $this->messages->first();
                    return [
                        'id' => $lastMessage->id,
                        'content' => $lastMessage->content,
                        'sender' => [
                            'id' => $lastMessage->sender->id,
                            'name' => $lastMessage->sender->name,
                        ],
                        'created_at' => $lastMessage->created_at->toIso8601String(),
                    ];
                }
            ),
        ];
    }
}
