<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invitation;
use App\Domain\Auth\Services\UserService;
use App\Http\Resources\InvitationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Invitations",
 *     description="Team invitation management"
 * )
 * REST API v1 controller for Invitation operations.
 */
class InvitationController extends BaseApiController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/invitations",
     *     summary="List all invitations",
     *     tags={"Invitations"},
     *
     *     @OA\Parameter(name="status", in="query", description="Invitation status", @OA\Schema(type="string")),
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
        $filters = [
            'status' => $request->get('status'),
        ];

        $invitations = $this->userService->paginateInvitations($filters, $request->integer('per_page', 15));

        return $this->paginated($invitations);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invitations/{id}",
     *     summary="Get a specific invitation",
     *     tags={"Invitations"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Invitation ID", @OA\Schema(type="integer")),
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
    public function show(Invitation $invitation): JsonResponse
    {
        $invitation->load(['invitedBy']);

        return $this->success(new InvitationResource($invitation));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invitations",
     *     summary="Send a team invitation",
     *     tags={"Invitations"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Invitation sent",
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string'],
        ]);

        $validated['company_id'] = activeCompany()->id;
        $validated['invited_by'] = auth()->id();

        $invitation = $this->userService->sendInvitation($validated);

        return $this->success(new InvitationResource($invitation), 'Invitation sent', 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/invitations/{id}",
     *     summary="Cancel an invitation",
     *     tags={"Invitations"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Invitation ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invitation cancelled",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Invitation $invitation): JsonResponse
    {
        $this->userService->cancelInvitation($invitation);

        return $this->success(null, 'Invitation cancelled');
    }
}
