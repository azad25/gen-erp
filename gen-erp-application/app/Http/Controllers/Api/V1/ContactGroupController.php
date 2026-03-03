<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customer\Contracts\ContactServiceInterface;
use App\Http\Resources\ContactGroupResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Contact Groups",
 *     description="Contact group management"
 * )
 * REST API v1 controller for Contact Group CRUD operations.
 */
class ContactGroupController extends BaseApiController
{
    public function __construct(
        private readonly ContactServiceInterface $contactService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/contact-groups",
     *     summary="List all contact groups",
     *     tags={"Contact Groups"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $groups = $this->contactService->getContactGroups(
            activeCompany()->id,
            $request->get('search'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($groups, ContactGroupResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/contact-groups/{id}",
     *     summary="Get a specific contact group",
     *     tags={"Contact Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Contact Group ID", @OA\Schema(type="integer")),
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
    public function show(int $id): JsonResponse
    {
        $contactGroup = $this->contactService->getContactGroup(activeCompany()->id, $id);
        $contactGroup->load(['customers', 'suppliers']);

        return $this->success(new ContactGroupResource($contactGroup));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contact-groups",
     *     summary="Create a new contact group",
     *     tags={"Contact Groups"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Contact group created",
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $group = $this->contactService->createContactGroup(activeCompany()->id, $validated);

        return $this->success(new ContactGroupResource($group), __('Contact group created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/contact-groups/{id}",
     *     summary="Update a contact group",
     *     tags={"Contact Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Contact Group ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contact group updated",
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
    public function update(Request $request, int $id): JsonResponse
    {
        $contactGroup = $this->contactService->getContactGroup(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $updatedGroup = $this->contactService->updateContactGroup($contactGroup, $validated);

        return $this->success(new ContactGroupResource($updatedGroup), __('Contact group updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/contact-groups/{id}",
     *     summary="Delete a contact group",
     *     tags={"Contact Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Contact Group ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contact group deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $contactGroup = $this->contactService->getContactGroup(activeCompany()->id, $id);
        $this->contactService->deleteContactGroup($contactGroup);

        return $this->success(null, __('Contact group deleted'));
    }
}
