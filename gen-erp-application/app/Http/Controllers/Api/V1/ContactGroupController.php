<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ContactGroup;
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
    /**
     * @OA\Get(
     *     path="/contact-groups",
     *     summary="List all contact groups",
     *     tags={"Contact Groups"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/ContactGroup")})), @OA\Property(property="message", type="string")))
     */
    public function index(Request $request): JsonResponse
    {
        $groups = ContactGroup::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($groups);
    }

    /**
     * @OA\Get(
     *     path="/contact-groups/{id}",
     *     summary="Get a specific contact group",
     *     tags={"Contact Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Contact Group ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/ContactGroup")))
     */
    public function show(ContactGroup $contactGroup): JsonResponse
    {
        $contactGroup->load(['customers', 'suppliers']);

        return $this->success($contactGroup);
    }

    /**
     * @OA\Post(
     *     path="/contact-groups",
     *     summary="Create a new contact group",
     *     tags={"Contact Groups"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
                @OA\Property(property="name", type="string"
            ), @OA\Property(property="description", type="string"))),
     *     @OA\Response(
     *         response=201,
     *         description="Contact group created",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/ContactGroup"), @OA\Property(property="message", type="string")))
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;

        $group = ContactGroup::create($validated);

        return $this->success($group, 'Contact group created', 201);
    }

    /**
     * @OA\Put(
     *     path="/contact-groups/{id}",
     *     summary="Update a contact group",
     *     tags={"Contact Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Contact Group ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
                @OA\Property(property="name", type="string"
            ), @OA\Property(property="description", type="string"))),
     *     @OA\Response(
     *         response=200,
     *         description="Contact group updated",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/ContactGroup"), @OA\Property(property="message", type="string")))
     */
    public function update(Request $request, ContactGroup $contactGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $contactGroup->update($validated);

        return $this->success($contactGroup->fresh(), 'Contact group updated');
    }

    /**
     * @OA\Delete(
     *     path="/contact-groups/{id}",
     *     summary="Delete a contact group",
     *     tags={"Contact Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Contact Group ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Contact group deleted",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="message", type="string")))
     */
    public function destroy(ContactGroup $contactGroup): JsonResponse
    {
        $contactGroup->delete();

        return $this->success(null, 'Contact group deleted');
    }
}
