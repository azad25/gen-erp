<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Designation;
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
    /**
     * @OA\Get(
     *     path="/designations",
     *     summary="List all designations",
     *     tags={"Designations"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Designation")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $designations = Designation::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($designations);
    }

    /**
     * @OA\Get(
     *     path="/designations/{id}",
     *     summary="Get a specific designation",
     *     tags={"Designations"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Designation ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Designation")
     *         )
     *     )
     * )
     */
    public function show(Designation $designation): JsonResponse
    {
        return $this->success($designation);
    }

    /**
     * @OA\Post(
     *     path="/designations",
     *     summary="Create a new designation",
     *     tags={"Designations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Designation created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Designation"),
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

        $validated['company_id'] = activeCompany()?->id;

        $designation = Designation::create($validated);

        return $this->success($designation, 'Designation created', 201);
    }

    /**
     * @OA\Put(
     *     path="/designations/{id}",
     *     summary="Update a designation",
     *     tags={"Designations"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Designation ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Designation updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Designation"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Designation $designation): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $designation->update($validated);

        return $this->success($designation->fresh(), 'Designation updated');
    }

    /**
     * @OA\Delete(
     *     path="/designations/{id}",
     *     summary="Delete a designation",
     *     tags={"Designations"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Designation ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Designation deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Designation $designation): JsonResponse
    {
        $designation->delete();

        return $this->success(null, 'Designation deleted');
    }
}
