<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification management"
 * )
 * REST API v1 controller for Notification operations.
 */
class NotificationController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/notifications",
     *     summary="List all notifications",
     *     tags={"Notifications"},
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Notification")})), @OA\Property(property="message", type="string")))
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($notifications);
    }

    /**
     * @OA\Get(
     *     path="/notifications/{id}",
     *     summary="Get a specific notification",
     *     tags={"Notifications"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Notification ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/Notification")))
     */
    public function show(Notification $notification): JsonResponse
    {
        return $this->success($notification);
    }

    /**
     * @OA\Post(
     *     path="/notifications/{notification}/mark-read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     @OA\Parameter(name="notification", in="path", required=true, description="Notification ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/Notification"), @OA\Property(property="message", type="string")))
     */
    public function markRead(Notification $notification): JsonResponse
    {
        $notification->update(['read_at' => now()]);

        return $this->success($notification->fresh(), 'Notification marked as read');
    }

    /**
     * @OA\Post(
     *     path="/notifications/mark-all-read",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="message", type="string")))
     */
    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read');
    }
}
