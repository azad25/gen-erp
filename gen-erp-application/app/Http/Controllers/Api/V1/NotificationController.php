<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use App\Domain\System\Services\SystemService;
use App\Http\Resources\NotificationResource;
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
    public function __construct(
        private SystemService $systemService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/notifications",
     *     summary="List all notifications",
     *     tags={"Notifications"},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $this->systemService->getUserNotifications(
            auth()->id(),
            activeCompany()->id,
            $request->integer('per_page', 15)
        );

        return $this->paginated($notifications);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notifications/{id}",
     *     summary="Get a specific notification",
     *     tags={"Notifications"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Notification ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(Notification $notification): JsonResponse
    {
        return $this->success(new NotificationResource($notification));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notifications/{notification}/mark-read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *
     *     @OA\Parameter(name="notification", in="path", required=true, description="Notification ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function markRead(Notification $notification): JsonResponse
    {
        $updatedNotification = $this->systemService->markNotificationAsRead($notification);

        return $this->success(new NotificationResource($updatedNotification), 'Notification marked as read');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notifications/mark-all-read",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function markAllRead(): JsonResponse
    {
        $this->systemService->markAllNotificationsAsRead(auth()->id());

        return $this->success(null, 'All notifications marked as read');
    }
}
