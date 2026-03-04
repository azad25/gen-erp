<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Http\Resources\NotificationResource;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Notification\Services\NotificationTranslatorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationTranslatorService $translator,
    ) {}

    // Bell dropdown — paginated, translated to user's language
    public function index(Request $request): JsonResponse
    {
        $notifications = ErpNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        // Translate each notification to user's language on the way out
        $translated = $notifications->through(function ($n) use ($request) {
            $texts = $this->translator->translateNotification($n, $request->user());
            return array_merge($n->toArray(), $texts);
        });

        return response()->json($translated);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => ErpNotification::where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        ErpNotification::where('user_id', $request->user()->id)
            ->findOrFail($id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        ErpNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        ErpNotification::where('user_id', $request->user()->id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['success' => true]);
    }
}