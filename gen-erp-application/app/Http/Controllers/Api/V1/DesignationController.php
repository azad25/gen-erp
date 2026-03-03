<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\HR\Contracts\HRServiceInterface;
use App\Http\Resources\DesignationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Designations",
 *     description="Designation management"
 * )
 * REST API v1 controller for Designation CRUD operations.
 */
class DesignationController extends BaseApiController
{
    public function __construct(
        private readonly HRServiceInterface $hrService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/designations",
     *     summary="List all designations",
     *     tags={"Designations"},
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
        $designations = $this->hrService->getDesignations(
            activeCompany()->id,
            $request->get('search'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($designations, DesignationResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/designations/{id}",
     *     summary="Get a specific designation",
     *     tags={"Designations"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Designation ID", @OA\Schema(type="integer")),
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
        $designation = $this->hrService->getDesignation(activeCompany()->id, $id);

        return $this->success(new DesignationResource($designation));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/designations",
     *     summary="Create a new designation",
     *     tags={"Designations"},
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
     *         description="Designation created",
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

        $designation = $this->hrService->createDesignation(activeCompany()->id, $validated);

        return $this->success(new DesignationResource($designation), __('Designation created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/designations/{id}",
     *     summary="Update a designation",
     *     tags={"Designations"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Designation ID", @OA\Schema(type="integer")),
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
     *         description="Designation updated",
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
        $designation = $this->hrService->getDesignation(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $updatedDesignation = $this->hrService->updateDesignation($designation, $validated);

        return $this->success(new DesignationResource($updatedDesignation), __('Designation updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/designations/{id}",
     *     summary="Delete a designation",
     *     tags={"Designations"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Designation ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Designation deleted",
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
        $designation = $this->hrService->getDesignation(activeCompany()->id, $id);
        $this->hrService->deleteDesignation($designation);

        return $this->success(null, __('Designation deleted'));
    }
}
