<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inbox\Services\InboxService;
use App\Http\Resources\Inbox\ConversationResource;
use App\Http\Resources\Inbox\MessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InboxController extends BaseApiController
{
    public function __construct(
        private readonly InboxService $inboxService
    ) {}

    public function conversations(Request $request): JsonResponse
    {
        $conversations = $this->inboxService->getConversationsForUser(
            $request->user()->id,
            activeCompany()->id,
            $request->only(['starred', 'search', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data' => ConversationResource::collection($conversations->items()),
            'message' => 'Success',
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'last_page' => $conversations->lastPage(),
            ],
        ]);
    }

    public function createDirectConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $conversation = $this->inboxService->createDirectConversation(
            $request->user()->id,
            $validated['user_id'],
            activeCompany()->id
        );

        return $this->success(
            new ConversationResource($conversation),
            __('inbox.conversation_created')
        );
    }

    public function createGroupConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'required|integer|exists:users,id',
        ]);

        $conversation = $this->inboxService->createGroupConversation(
            $request->user()->id,
            $validated['participant_ids'],
            $validated['title'],
            activeCompany()->id
        );

        return $this->success(
            new ConversationResource($conversation),
            __('inbox.group_created')
        );
    }

    public function messages(Request $request, int $conversationId): JsonResponse
    {
        $messages = $this->inboxService->getMessages(
            $conversationId,
            $request->user()->id,
            activeCompany()->id,
            $request->integer('page', 1),
            $request->integer('per_page', 50)
        );

        return response()->json([
            'success' => true,
            'data' => MessageResource::collection($messages->items()),
            'message' => 'Success',
            'meta' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $message = $this->inboxService->sendMessage(
            $conversationId,
            $request->user()->id,
            $validated['content'],
            $request->file('attachments', [])
        );

        return $this->success(
            new MessageResource($message),
            __('inbox.message_sent')
        );
    }

    public function editMessage(Request $request, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $message = $this->inboxService->editMessage(
            $messageId,
            $request->user()->id,
            $validated['content']
        );

        return $this->success(
            new MessageResource($message),
            __('inbox.message_updated')
        );
    }

    public function deleteMessage(Request $request, int $messageId): JsonResponse
    {
        $this->inboxService->deleteMessage($messageId, $request->user()->id);

        return $this->success(null, __('inbox.message_deleted'));
    }

    public function deleteConversation(Request $request, int $conversationId): JsonResponse
    {
        $this->inboxService->deleteConversation($conversationId, $request->user()->id);

        return $this->success(null, __('inbox.conversation_deleted'));
    }

    public function addParticipants(Request $request, int $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
        ]);

        $this->inboxService->addParticipants(
            $conversationId,
            $validated['user_ids'],
            $request->user()->id
        );

        return $this->success(null, __('inbox.participants_added'));
    }

    public function removeParticipant(Request $request, int $conversationId, int $userId): JsonResponse
    {
        $this->inboxService->removeParticipant(
            $conversationId,
            $userId,
            $request->user()->id
        );

        return $this->success(null, __('inbox.participant_removed'));
    }

    public function toggleStar(Request $request, int $conversationId): JsonResponse
    {
        $conversation = \App\Domain\Inbox\Models\Conversation::forCompany(activeCompany()->id)
            ->findOrFail($conversationId);

        $isStarred = $conversation->toggleStar($request->user()->id);

        return $this->success(
            ['is_starred' => $isStarred],
            $isStarred ? __('inbox.conversation_starred') : __('inbox.conversation_unstarred')
        );
    }

    public function toggleMute(Request $request, int $conversationId): JsonResponse
    {
        $conversation = \App\Domain\Inbox\Models\Conversation::forCompany(activeCompany()->id)
            ->findOrFail($conversationId);

        $isMuted = $conversation->toggleMute($request->user()->id);

        return $this->success(
            ['is_muted' => $isMuted],
            $isMuted ? __('inbox.conversation_muted') : __('inbox.conversation_unmuted')
        );
    }

    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        $conversation = \App\Domain\Inbox\Models\Conversation::forCompany(activeCompany()->id)
            ->findOrFail($conversationId);

        $conversation->markAsRead($request->user()->id);

        return $this->success(null, __('inbox.marked_as_read'));
    }

    public function companyUsers(Request $request): JsonResponse
    {
        $users = $this->inboxService->getCompanyUsers(
            activeCompany()->id,
            $request->user()->id
        );

        return $this->success($users);
    }

    public function downloadAttachment(Request $request, int $attachmentId): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $attachment = \App\Domain\Inbox\Models\MessageAttachment::forCompany(activeCompany()->id)
            ->findOrFail($attachmentId);

        // Verify user has access to this attachment's conversation
        $conversation = $attachment->message->conversation;
        if (!$conversation->participants()->where('users.id', $request->user()->id)->exists()) {
            abort(403, 'Unauthorized');
        }

        return Storage::disk('private')->download($attachment->file_path, $attachment->file_name);
    }
}
